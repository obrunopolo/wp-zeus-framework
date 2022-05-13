<?php

namespace Zeus\Framework;

use ReflectionClassConstant;
use Zeus\Framework\Contracts\Controller;
use Zeus\Framework\Contracts\Endpoint;
use Zeus\Framework\Contracts\Singleton;

class RoutesFramework extends Singleton implements Controller
{

    public function registerRestEndpoints()
    {

        $postType = new ReflectionClassConstant(static::class, "ENDPOINTS");

        foreach ($postType->getValue() as $namespace => $endpoints) {
            foreach ($endpoints as $route => $endpoint) {
                $endpoint = call_user_func([$endpoint, 'getInstance']);
                if ($endpoint instanceof Endpoint) {
                    $endpoint->registerEndpoint($namespace, $route);
                }
            }
        }
    }


    final function run()
    {
        add_action('rest_api_init', [$this, 'registerRestEndpoints']);
    }
}
