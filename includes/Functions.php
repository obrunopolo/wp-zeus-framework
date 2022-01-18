<?php

use Zeus\App;



/**
 * Returns the instance of `\Zeus\App` class.
 *
 * Is the entry point to access the plugin funcionalities.
 *
 * @return App
 */
function zeus()
{
    return App::getInstance();
    // if (is_null($GLOBALS['zeus'])) {
    //     $GLOBALS['zeus'] = App::getInstance();
    // }
    // return $GLOBALS['zeus'];
}

/**
 * Returns true if running in development mode, false otherwise.
 *
 * To change the environment, add one of the following lines to your `wp-config` file:
 *
 * ```
 * define("ZEUS_ENV", "development"); // default
 * // OR
 * define("ZEUS_ENV", "production");
 * ```
 *
 * @return bool
 */
function is_dev()
{
    return ZEUS_ENV === "development";
}

/**
 * Returns true if running in production mode, false otherwise.
 *
 * To change the environment, add one of the following lines to your `wp-config` file:
 *
 * ```
 * define("ZEUS_ENV", "development"); // default
 * // OR
 * define("ZEUS_ENV", "production");
 * ```
 *
 * @return bool
 */
function is_production()
{
    return ZEUS_ENV === "production";
}
