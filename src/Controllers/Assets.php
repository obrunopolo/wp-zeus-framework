<?php

namespace Zeus\Controllers;

use Masterminds\HTML5;
use Zeus\Framework\AssetsFramework;

class Assets extends AssetsFramework
{

    const OPTION_JS_ENTRIES = "zeus_js_entries";
    const OPTION_CSS_ENTRIES = "zeus_css_entries";
    const OPTION_LAST_VERSION = "zeus_assets_last_version";

    const ASSETS_PREFIX = "zeus-";

    public function run()
    {

        // Determine when assets should be loaded.
        add_filter("zeus_enqueues_js_helloworld", "__return_true");
        add_filter("zeus_enqueues_css_helloworld", "__return_true");

        // Enqueue files
        add_action("wp_enqueue_scripts", [$this, "enqueueScripts"], 20);

        // Renew file references
        add_action("zeus_deploy", [$this, "updateAssets"]);

        if (ZEUS_ALWAYS_CHECK_CHUNKS) {
            do_action("zeus_deploy");
        }

        if (ZEUS_ENABLE_AUTODEPLOY === true && get_option(self::OPTION_LAST_VERSION) !== zeus()->getVersion()) {
            add_action("shutdown", function () {
                if (!did_action("zeus_deploy")) {
                    do_action("zeus_deploy");
                }
            });
        }
    }
}
