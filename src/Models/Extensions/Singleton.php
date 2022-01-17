<?php

namespace Zeus\Models\Extensions;

trait Singleton
{

    private static $instance;

    private function __construct()
    {
        if (method_exists($this, 'run')) {
            $this->run();
        }
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }
}
