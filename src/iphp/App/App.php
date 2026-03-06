<?php

namespace Iphp\App;

class App
{
    public function __construct()
    {
        echo "App constructor called\n";
    }

    public function run()
    {
        echo "App is running\n";
    }

    public function __destruct()
    {
        echo "App destructor called\n";
    }

}
