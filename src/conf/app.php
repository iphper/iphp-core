<?php

return [
    
    // 注册组件
    'register' => [
        'app' => \Iphp\App\App::class,
        'conf' => \Iphp\Conf\Conf::class,
        'error' => \Iphp\Error\Error::class,
    ],

    // 应用注册服务
    'servers' => [
        'http' => \Iphp\Http\Server::class,
        'command' => \Iphp\Command\Server::class,
    ],
];
