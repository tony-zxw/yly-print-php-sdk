# yly-print-php-sdk

### Requirement

```
PHP >= 7.0
```
### Installation
~~composer require tony-zxw/yly-print-php-sdk:dev-master~~
```shell
composer require tony-zxw/yly-print-php-sdk
```


### Usage
  1. [project github url](https://github.com/tony-zxw/yly-print-php-sdk)
  1. [yilianyun doc](https://dev.10ss.net/doc/doc)
  1. [yilianyun api doc](http://doc2.10ss.net/331992)
    
```php
<?php
//composer下加载方式
require_once "vendor\autoload.php";
use YLYPlatform\Factory;

// 初始化配置 
$config = [
    /**
     * 账号基本信息，
     */
    'client_id' => 'your-client-id',
    'client_secret' => 'your-client-secret',

    /**
     * 日志配置
     *
     * level: 日志级别, 可选为：
     *        debug/info/notice/warning/error/critical/alert/emergency
     * path：日志文件位置(绝对路径!!!)，要求可写权限
     */
    'log' => [
        'default' => 'dev', // 默认使用的 channel，生产环境可以改为下面的 prod
        'channels' => [
            // 测试环境
            'dev' => [
                'driver' => 'single',
                'path' => __DIR__ . '/tmp/ylyplatform.log',
                'level' => 'debug',
            ],
            // 生产环境
            'prod' => [
                'driver' => 'daily',
                'path' => __DIR__ . '/tmp/ylyplatform.log',
                'level' => 'info',
            ],
        ],
    ],

    'http' => [
        'verify' => false, //solve cURL error 77: error setting certificate verify locations
        // 'base_uri' => 'https://open-api.10ss.net/', 
    ],

];
// 自有应用服务模式
$app = Factory::clientMode($config);

//获取token
$token = $app['access_token']->getToken();
// or $token = $app->access_token->getToken();
var_dump($token);

//授权打印机(自有型应用使用,开放型应用请跳过该步骤)
$data = $app['printer']->addPrinter('machineCode', 'mSign');
var_dump($data);

//调取文本打印
$data = $app->print->index('machineCode', 'content', 'originId');
var_dump($data);

//调取图形打印
$app->picture_print->index('machineCode', 'pictureUrl', 'originId');
var_dump($data);

```