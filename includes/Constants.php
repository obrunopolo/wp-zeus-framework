<?php

define("ZEUS_ABSPATH", dirname(__DIR__));
define("ZEUS_URL", get_site_url() . "/wp-content/plugins/zeus-framework");

if (!defined("ZEUS_DEV")) {
    define("ZEUS_DEV", true);
}

if (!defined("ZEUS_DISABLE_AUTODEPLOY")) {
    define("ZEUS_DISABLE_AUTODEPLOY", ZEUS_DEV === false);
}
