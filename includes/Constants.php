<?php

/**
 * The path to plugin root directory.
 */
define("ZEUS_ABSPATH", dirname(__DIR__));

/**
 * The public URL to plugin directory.
 */
define("ZEUS_URL", get_site_url() . "/wp-content/plugins/zeus-framework");

if (!defined("ZEUS_ENV")) {
    /**
     * The environment that the application is running in. "development" or "production".
     */
    define("ZEUS_ENV", "development");
}

if (!defined("ZEUS_ENABLE_AUTODEPLOY")) {
    /**
     * If plugin should deploy itself when the version changes.
     * Setting to false does not build the project, but updates
     * the generated assets references.
     */
    define("ZEUS_ENABLE_AUTODEPLOY", true);
}


if (!defined("ZEUS_ALWAYS_CHECK_CHUNKS")) {
    /**
     * If plugin should always deploy itself. Useful for developing environment.
     */
    define("ZEUS_ALWAYS_CHECK_CHUNKS", false);
}
