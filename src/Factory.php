<?php

namespace YLYPlatform;
/**
 * Class Factory
 *
 * @method static \YLYPlatform\ClientMode\Application printer(array $config)
 */
class Factory
{
    public static function make($name, array $config)
    {
        $namespace = Kernel\Support\Str::studly($name);
        $application = "\\YLYPlatform\\{$namespace}\\Application";

        return new $application($config);
    }

    public static function __callStatic($name, $arguments)
    {
        return self::make($name, ...$arguments);
    }


}