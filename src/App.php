<?php

namespace Zeus;

use Zeus\Controllers\Assets;
use Zeus\Models\Extensions\Controller;
use Zeus\Models\Extensions\Singleton;

class App extends Singleton
{

    const PLUGIN_NAME = "zeus-framework";
    const PLUGIN_VERSION = "1.0.0";

    private $run_complete = false;

    /** @var Assets */
    public $assets;

    // use \Zeus\Models\Extensions\Singleton;

    public function init()
    {
        // echo "Zeus started";
    }

    public function getVersion()
    {
        return self::PLUGIN_VERSION;
    }

    public function run()
    {
        // prevent re-running
        if ($this->run_complete === true) {
            return;
        }


        // Instantiate controllers here
        $this->assets = Assets::getInstance();

        // Create hooks here
        add_action('init', [$this, 'init']);

        $this->run_complete = true;
    }
}
