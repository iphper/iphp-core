<?php

namespace Iphp\Error;

use Iphp\Server\Server;

class Error extends Server
{
    public function error(\Throwable $e)
    {
        // 错误处理逻辑
        echo "Error: " . $e->getMessage() . "<br />";
    }
}
