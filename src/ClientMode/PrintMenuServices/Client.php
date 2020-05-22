<?php

namespace YLYPlatform\ClientMode\PrintMenuServices;


use YLYPlatform\Kernel\BaseClient;

class Client extends BaseClient
{
    /**
     * http://doc2.10ss.net/372521
     * 添加应用菜单
     * 请求地址:https://open-api.10ss.net/printmenu/addprintmenu
     * 请求方式:POST
     * 注意： 仅支持除k4-WA，k4-WH外的k4或w1机型．唤醒应用菜单请使用<MA>打印指令，详情请看打印机指令
     */
    public function add(string $machineCode, string $content)
    {
        $params = [
            'machine_code' => $machineCode,
            'content' => $content,
        ];

        return $this->httpPostJson('printmenu/addprintmenu', $params);
    }
}