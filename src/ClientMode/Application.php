<?php

namespace YLYPlatform\ClientMode;


use YLYPlatform\Kernel\ServiceContainer;

/**
 * Class Application
 * @package YLYPlatform\ClientMode
 *
 * @property \YLYPlatform\ClientMode\OAuth\AccessToken                $access_token
 * @property \YLYPlatform\ClientMode\PrintMenuServices\Client         $print_menu
 * @property \YLYPlatform\ClientMode\PrinterServices\Client           $printer
 * @property \YLYPlatform\ClientMode\PrintServices\Client             $print
 * @property \YLYPlatform\ClientMode\PicturePrintServices\Client      $picture_print
 * @property \YLYPlatform\ClientMode\ExpressPrintServices\Client      $express_print
 */
class Application extends ServiceContainer
{
    protected $providers = [
        OAuth\ServiceProvider::class,
        PrintMenuServices\ServiceProvider::class,
        PrinterServices\ServiceProvider::class,
        PrintServices\ServiceProvider::class,
        PicturePrintServices\ServiceProvider::class,
        ExpressPrintServices\ServiceProvider::class,
    ];
}