<?php

namespace Zeus;

use Zeus\Controllers\Assets;
use Zeus\Models\Extensions\Controller;

class App implements Controller
{

    use \Zeus\Models\Extensions\Singleton;

    public function init()
    {
        // echo "Zeus started";
    }

    public function run()
    {

        // Instantiate controllers here
        Assets::getInstance();

        // Create hooks here
        add_action('init', [$this, 'init']);
    }
}
