<?php

namespace Zeus;

use Zeus\Api\Routes;
use Zeus\Controllers\Assets;
use Zeus\Framework\Contracts\Endpoint;
use Zeus\Framework\Contracts\Singleton;

class App extends Singleton
{

    const PLUGIN_NAME = "zeus-framework";
    const PLUGIN_VERSION = "1.0.0";

    /** @var Assets */
    public $assets;

    public function getVersion()
    {
        return self::PLUGIN_VERSION;
    }


    public function init()
    {
        do_action('zeus_register_post_types');
    }

    public function registerPostTypes()
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
            add_action('save_post_' . $post_type, [$class, 'onPostSave']);
        }
    }


    public function run()
    {

        // Instantiate controllers here
        $this->assets = Assets::getInstance();

        // initialize routes
        Routes::getInstance();

        // Create hooks here
        add_action('init', [$this, 'init']);
        add_action('zeus_register_post_types', [$this, 'registerPostTypes']);
    }
}
