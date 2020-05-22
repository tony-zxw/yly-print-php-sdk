<?php

namespace YLYPlatform\Kernel;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use YLYPlatform\Kernel\Contracts\AccessTokenInterface;
use YLYPlatform\Kernel\Exceptions\HttpException;
use YLYPlatform\Kernel\Exceptions\RuntimeException;
use YLYPlatform\Kernel\Exceptions\InvalidArgumentException;
use YLYPlatform\Kernel\Traits\HasHttpRequests;
use YLYPlatform\Kernel\Traits\InteractsWithCache;

abstract class AccessToken implements AccessTokenInterface
{
    use HasHttpRequests;
    use InteractsWithCache;
    /**
     * @var \YLYPlatform\Kernel\ServiceContainer
     */
    protected $app;
    /**
     * @var string
     */
    protected $requestMethod = 'POST';

    /**
     * @var string
     */
    protected $endpointToGetToken;

    /**
     * @var string
     */
    protected $queryName;

    /**
     * @var array
     */
    protected $token;

    /**
     * @var string
     */
    protected $tokenKey = 'access_token';
    protected $refreshTokenKey = 'refresh_token';
    protected $machineCodeKey = 'machine_code';
    protected $errorKey = 'error';
    protected $bodyKey = 'body';

    /**
     * @var string
     */
    protected $cachePrefix = 'ylyplatform.kernel.access_token.';

    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
    }

    public function getRefreshedToken(): array
    {
        return $this->getToken(true);
    }

    public function getToken(bool $refresh = false): array
    {
        $cacheKey = $this->getCacheKey();
        $cache = $this->getCache();

        if (!$refresh && $cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $refresh_token = '';
        $lastToken = null;
        if ($refresh && $cache->has($cacheKey)) {
            $lastToken = $cache->get($cacheKey);
            $refresh_token = $lastToken[$this->refreshTokenKey];
        }

        $token = $this->requestToken($this->getCredentials($refresh_token), true);
        $this->setToken(
            $token[$this->bodyKey][$this->tokenKey],
            $token['expires_in'] ?? 2592000,
            $token[$this->bodyKey][$this->refreshTokenKey] ?? '',
            $token[$this->bodyKey][$this->machineCodeKey] ?? ''
        );

        return $token;

    }


    public function setToken(string $token, int $lifetime = 2592000, string $refresh_token = '', string $machine_code = ''): AccessTokenInterface
    {
        //        {"error":"0","error_description":"success","body":{"access_token":"839cdb6fc9f0422092bb011f620f7d8a","refresh_token":"16ecc32c7bc6455a84aaf0fbe8351081","machine_code":"","expires_in":2592000,"scope":"all"}}
        $this->getCache()->set($this->getCacheKey(), [
            $this->tokenKey => $token,
            'expires_in' => $lifetime,
            $this->refreshTokenKey => $refresh_token,
            $this->machineCodeKey => $machine_code,
        ], $lifetime);

        if (!$this->getCache()->has($this->getCacheKey())) {
            throw new RuntimeException('Failed to cache access token.');
        }

        return $this;
    }

    public function refresh(): AccessTokenInterface
    {
        $this->getToken(true);
        return $this;
    }

    public function requestToken(array $credentials, $toArray = false)
    {
//        {"error":"0","error_description":"success","body":{"access_token":"839cdb6fc9f0422092bb011f620f7d8a","refresh_token":"16ecc32c7bc6455a84aaf0fbe8351081","machine_code":"","expires_in":2592000,"scope":"all"}}
        $response = $this->sendRequest($credentials);
        $result = json_decode($response->getBody()->getContents(), true);
        $formatted = $this->castResponseToType($response, $this->app['config']->get('response_type'));

        if (!isset($result[$this->errorKey]) || $result[$this->errorKey] != "0" || empty($result[$this->bodyKey])) {
            throw new HttpException('Request access_token fail: ' . json_encode($result, JSON_UNESCAPED_UNICODE), $response, $formatted);
        }

        if (empty($result[$this->bodyKey][$this->tokenKey])) {
            throw new HttpException('Request access_token fail tokenKey not existed: ' . json_encode($result, JSON_UNESCAPED_UNICODE), $response, $formatted);
        }

        return $toArray ? $result : $formatted;

    }

    public function applyToRequest(RequestInterface $request, array $requestOptions = []): RequestInterface
    {
        parse_str($request->getUri()->getQuery(), $query);

        $query = http_build_query(array_merge($this->getQuery(), $query, $requestOptions));

        return $request->withUri($request->getUri()->withQuery($query));
    }

    protected function sendRequest(array $credentials): ResponseInterface
    {
        $options = [
            ('GET' === $this->requestMethod) ? 'query' : 'json' => $credentials,
        ];

        return $this->setHttpClient($this->app['http_client'])->request($this->getEndpoint(), $this->requestMethod, $options);

    }

    protected function getQuery(): array
    {
        return [$this->queryName ?? $this->tokenKey => $this->getToken()[$this->tokenKey]];
    }

    /**
     * @return string
     *
     * @throws \YLYPlatform\Kernel\Exceptions\InvalidArgumentException
     */
    public function getEndpoint(): string
    {
        if (empty($this->endpointToGetToken)) {
            throw new InvalidArgumentException('No endpoint for access token request.');
        }

        return $this->endpointToGetToken;
    }

    protected function getCacheKey()
    {
//        return $this->cachePrefix . md5(json_encode($this->getCredentials()));
        return $this->cachePrefix . md5(json_encode($this->genCacheKey($this->getCredentials())));
    }

    /**
     * @return string
     */
    public function getTokenKey()
    {
        return $this->tokenKey;
    }

    /**
     * Credential for get token.
     *
     * @return array
     */
    abstract protected function getCredentials(string $refresh_token = ''): array;

    /**
     * cache key for get token
     * @return array
     */
    abstract protected function genCacheKey(array $credentials): array;

}