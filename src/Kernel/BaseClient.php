<?php

namespace YLYPlatform\Kernel;


use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use YLYPlatform\Kernel\Contracts\AccessTokenInterface;
use YLYPlatform\Kernel\Http\Response;
use YLYPlatform\Kernel\Traits\HasHttpRequests;

class BaseClient
{
    use HasHttpRequests {
        request as performRequest;
    }

    /**
     * @var \YLYPlatform\Kernel\ServiceContainer
     */
    protected $app;

    /**
     * @var \YLYPlatform\Kernel\Contracts\AccessTokenInterface
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $baseUri;

    public function __construct(ServiceContainer $app, AccessTokenInterface $accessToken = null)
    {
        $this->app = $app;
        $this->accessToken = $accessToken ?? $this->app['access_token'];
    }

    /**
     * Credential for get token.
     *
     * @return array
     */
    protected function getCredentials(): array
    {
        $client_id = $this->app['config']['client_id'];
        $timestamp = time();
        $client_secret = $this->app['config']['client_secret'];
        $sign = md5($client_id . $timestamp . $client_secret);
        $accessToken = $this->accessToken->getToken();

        $credentials = [
            'client_id' => $client_id,
            'access_token' => $accessToken['access_token'],
            'sign' => $sign,
            'timestamp' => $timestamp,
            'id' => uuid4(),
        ];

        return $credentials;
    }


    protected function genCredentialParams(array $params = []): array
    {
        return array_merge($this->getCredentials(), $params);
    }

    /**
     * GET request.
     *
     * @param string $url
     * @param array $query
     *
     * @return \Psr\Http\Message\ResponseInterface|\YLYPlatform\Kernel\Support\Collection|array|object|string
     *
     * @throws \YLYPlatform\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpGet(string $url, array $query = [])
    {
        $query = $this->genCredentialParams($query);
        return $this->request($url, 'GET', ['query' => $query]);
    }

    /**
     * POST request.
     *
     * @param string $url
     * @param array $data
     *
     * @return \Psr\Http\Message\ResponseInterface|\YLYPlatform\Kernel\Support\Collection|array|object|string
     *
     * @throws \YLYPlatform\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpPost(string $url, array $data = [])
    {
        $data = $this->genCredentialParams($data);
        return $this->request($url, 'POST', ['form_params' => $data]);
    }

    /**
     * JSON request.
     *
     * @param string $url
     * @param array $data
     * @param array $query
     *
     * @return \Psr\Http\Message\ResponseInterface|\YLYPlatform\Kernel\Support\Collection|array|object|string
     *
     * @throws \YLYPlatform\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpPostJson(string $url, array $data = [], array $query = [])
    {
        $data = $this->genCredentialParams($data);
        return $this->request($url, 'POST', ['query' => $query, 'json' => $data]);
    }

    /**
     * Upload file.
     *
     * @param string $url
     * @param array $files
     * @param array $form
     * @param array $query
     *
     * @return \Psr\Http\Message\ResponseInterface|\YLYPlatform\Kernel\Support\Collection|array|object|string
     *
     * @throws \YLYPlatform\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpUpload(string $url, array $files = [], array $form = [], array $query = [])
    {
        $query = $this->genCredentialParams($query);

        $multipart = [];

        foreach ($files as $name => $path) {
            $multipart[] = [
                'name' => $name,
                'contents' => fopen($path, 'r'),
            ];
        }

        foreach ($form as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }

        return $this->request($url, 'POST', ['query' => $query, 'multipart' => $multipart, 'connect_timeout' => 30, 'timeout' => 30, 'read_timeout' => 30]);
    }

    /**
     * @return AccessTokenInterface
     */
    public function getAccessToken(): AccessTokenInterface
    {
        return $this->accessToken;
    }

    /**
     * @param \YLYPlatform\Kernel\Contracts\AccessTokenInterface $accessToken
     *
     * @return $this
     */
    public function setAccessToken(AccessTokenInterface $accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $options
     * @param bool $returnRaw
     *
     * @return \Psr\Http\Message\ResponseInterface|\YLYPlatform\Kernel\Support\Collection|array|object|string
     *
     * @throws \YLYPlatform\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $url, string $method = 'GET', array $options = [], $returnRaw = false)
    {
        if (empty($this->middlewares)) {
            $this->registerHttpMiddlewares();
        }

        $response = $this->performRequest($url, $method, $options);
//        $this->app->events->dispatch(new Events\HttpResponseCreated($response));
        return $returnRaw ? $response : $this->castResponseToType($response, $this->app->config->get('response_type'));
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $options
     *
     * @return \YLYPlatform\Kernel\Http\Response
     *
     * @throws \YLYPlatform\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function requestRaw(string $url, string $method = 'GET', array $options = [])
    {
        return Response::buildFromPsrResponse($this->request($url, $method, $options, true));
    }

    /**
     * Register Guzzle middlewares.
     */
    protected function registerHttpMiddlewares()
    {
        // retry
        $this->pushMiddleware($this->retryMiddleware(), 'retry');
        // access token
        $this->pushMiddleware($this->accessTokenMiddleware(), 'access_token');
        // log
        $this->pushMiddleware($this->logMiddleware(), 'log');
    }

    /**
     * Attache access token to request query.
     *
     * @return \Closure
     */
    protected function accessTokenMiddleware()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if ($this->accessToken) {
                    $request = $this->accessToken->applyToRequest($request, $options);
                }
                return $handler($request, $options);
            };
        };
    }

    /**
     * Log the request.
     *
     * @return \Closure
     */
    protected function logMiddleware()
    {
        $formatter = new MessageFormatter($this->app['config']['http.log_template'] ?? MessageFormatter::DEBUG);
        return Middleware::log($this->app['logger'], $formatter, LogLevel::DEBUG);
    }

    /**
     * Return retry middleware.
     *
     * @return \Closure
     */
    protected function retryMiddleware()
    {
        return Middleware::retry(function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null
        ) {
            // Limit the number of retries to 2
            if ($retries < $this->app->config->get('http.max_retries', 1) && $response && $body = $response->getBody()) {
                // Retry on server errors
                $response = json_decode($body, true);

                if (!empty($response['error']) && in_array(abs($response['error']), [18], true)) {
                    $this->accessToken->refresh();
                    $this->app['logger']->debug('Retrying with refreshed access token.');

                    return true;
                }
            }

            return false;
        }, function () {
            return abs($this->app->config->get('http.retry_delay', 500));
        });
    }
}