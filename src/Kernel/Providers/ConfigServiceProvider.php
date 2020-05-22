<?php

namespace YLYPlatform\Kernel\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use YLYPlatform\Kernel\Config;

class ConfigServiceProvider implements ServiceProviderInterface
{

    public function register(Container $pimple)
    {
        $pimple['config'] = function ($app) {
            return new Config($app->getConfig());
        };
    }
}