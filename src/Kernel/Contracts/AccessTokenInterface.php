<?php

namespace YLYPlatform\Kernel\Contracts;


use Psr\Http\Message\RequestInterface;

interface AccessTokenInterface
{
    /**
     * @return array
     */
    public function getToken(): array;

    /**
     * @return \YLYPlatform\Kernel\Contracts\AccessTokenInterface
     */
    public function refresh(): self;

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @param array                              $requestOptions
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function applyToRequest(RequestInterface $request, array $requestOptions = []): RequestInterface;
}