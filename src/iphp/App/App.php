<?php

namespace Iphp\App;

use Iphp\Server\ServerInterface;
use Iphp\Container\Container;

class App extends Container implements ServerInterface
{
    public function init()
    {
        // 初始化逻辑
    }

    // 加载应用资源
    public function load()
    {
        // 加载逻辑
    }

    // 执行应用
    public function exec()
    {
        // 执行时加载
        $this->load();

        try {
            // 运行主逻辑
            $this->run();
        } catch (\Throwable $e) {
            // 错误处理
            $this->error($e);
        }
    }

    public function run()
    {
        // 主逻辑
    }

    public function error(\Throwable $e)
    {
        // 异常处理逻辑
    }

    public function destroy()
    {
        // 销毁逻辑
    }

    protected function __construct()
    {
        parent::__construct();

        $this->init();

    }

    public function __destruct()
    {
        $this->destroy();
    }

}
