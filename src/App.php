<?php

namespace Zeus;

use Zeus\Controllers\Assets;
use Zeus\Models\Singleton;

class App extends Singleton
{

    const PLUGIN_NAME = "zeus-framework";
    const PLUGIN_VERSION = "1.0.0";

    private $run_complete = false;

    /** @var Assets */
    public $assets;

    public function getVersion()
    {
        return self::PLUGIN_VERSION;
    }

    public function init()
    {
        $this->registerPostTypes();
    }

    private function registerPostTypes()
    {
        // Add post classes to this array.
        $post_types = [];

        // Change this to match the folder where post types are created.
        $namespace = "\Zeus\Models\Post";

        $classes = array_map(function ($post_type) use ($namespace) {
            return $namespace . "\\" . $post_type;
        }, $post_types);

        foreach ($classes as $class) {
            call_user_func([$class, 'registerPostType']);
            $post_type = constant($class . '::POST_TYPE');
            add_action('save_post_' . $post_type, [$class, 'on_post_save']);
        }

        // print_r($classes);

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
