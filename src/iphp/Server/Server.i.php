<?php

namespace Iphp\Server;

use \Throwable;

interface ServerInterface
{
    // 初始化
    public function init();

    // 加载
    public function load();

    // 运行
    public function exec();

    // 异常
    public function error(Throwable $e);

    // 销毁
    public function destroy();

}
