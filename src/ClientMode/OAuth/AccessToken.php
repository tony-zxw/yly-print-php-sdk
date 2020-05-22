<?php

namespace YLYPlatform\ClientMode\OAuth;

use YLYPlatform\Kernel\AccessToken as BaseAccessToken;

class AccessToken extends BaseAccessToken
{
    /**
     * @var string
     */
    protected $endpointToGetToken = 'https://open-api.10ss.net/oauth/oauth';

    /**
     * Credential for get token.
     *
     * @return array
     */
    protected function getCredentials(string $refresh_token = ''): array
    {
        $client_id = $this->app['config']['client_id'];
        $timestamp = time();
        $client_secret = $this->app['config']['client_secret'];
        $sign = md5($client_id . $timestamp . $client_secret);

        $credentials = [
            'grant_type' => 'client_credentials',
            'scope' => 'all',
            'client_id' => $client_id,
//            'secret' => $this->app['config']['secret'],
            'sign' => $sign,
            'timestamp' => $timestamp,
            'id' => uuid4(),
        ];

        return $credentials;
    }

    protected function genCacheKey(array $credentials): array
    {
        return [
            'grant_type' => $credentials['grant_type'],
            'scope' => $credentials['scope'],
            'client_id' => $credentials['client_id'],
            'secret' => $this->app['config']['secret'],
//            'sign' => $sign,
//            'timestamp' => $timestamp,
//            'id' => uuid4(),
        ];
    }
}