<?php

namespace Iphp\Server;

class Server implements ServerInterface
{
    public function init()
    {
        // 初始化逻辑
    }

    public function load()
    {
        // 加载逻辑
    }

    public function exec()
    {
        // 运行逻辑
    }

    public function error(\Throwable $e)
    {
        // 异常处理逻辑
    }

    public function destroy()
    {
        // 销毁逻辑
    }
}
