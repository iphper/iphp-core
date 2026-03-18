<?php

namespace Iphp\Command;

class Server extends \Iphp\Server\Server
{
    public function init()
    {
        // HTTP服务器初始化逻辑
    }

    public function load()
    {
        // HTTP服务器加载逻辑
    }

    public function exec()
    {
        // HTTP服务器运行逻辑
    }

    public function error(\Throwable $e)
    {
        // HTTP服务器异常处理逻辑
        echo "HTTP Server Error: " . $e->getMessage() . "<br />";
    }

    public function destroy()
    {
        // HTTP服务器销毁逻辑
    }
}