<?php

namespace Zeus\Models\Extensions;

use Zeus\App;

/**
 * Original Singleton class.
 *
 * @link https://www.polynique.com/coding/extending-singleton-in-php-to-avoid-boilerplate-code/
 */
abstract class Singleton
{

    /**
     * Any Singleton class.
     *
     * @var Singleton[] $instances
     */
    private static $instances = array();

    /**
     * Consctruct.
     * Private to avoid "new".
     */
    private function __construct()
    {
        if ($this instanceof Controller) {
            $this->run();
        }
    }

    /**
     * Get Instance
     *
     * @return Singleton
     */
    public static function getInstance()
    {

        if (!isset($instances[static::class])) {
            self::$instances[static::class] = new static();
        }

        return self::$instances[static::class];
    }

    /**
     * Avoid clone instance
     */
    private function __clone()
    {
    }

    /**
     * Avoid serialize instance
     */
    private function __sleep()
    {
    }

    /**
     * Avoid unserialize instance
     */
    private function __wakeup()
    {
    }
}
