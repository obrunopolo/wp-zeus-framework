<?php

namespace Zeus\Models;

use ReflectionClassConstant;
use ReflectionMethod;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Abstract class for `wp-json` API endpoints.
 *
 * Extend this class to create a new endpoint.
 *
 * @package Zeus\Models
 */
abstract class Endpoint extends Singleton
{

    const NAMESPACE = "zeus/v1";

    /**
     * Gets the route string for this endpoint.
     *
     * @return string|false
     */
    public function getRoute()
    {
        try {
            $constant = new ReflectionClassConstant($this, 'ROUTE');
            return $constant->getValue();
        } catch (\Throwable $th) {
            // Url constant does not exist.
            return false;
        }
    }

    /**
     * Registers the endpoint.
     *
     * @return void
     */
    public function registerEndpoint()
    {
        $methods = ['get', 'post', 'put', 'delete', 'patch'];

        $route = $this->getRoute();

        if ($route === false) {
            return;
        }

        foreach ($methods as $method) {
            $reflector = new ReflectionMethod($this, $method);
            if (static::class === $reflector->getDeclaringClass()->getName()) {
                register_rest_route(static::NAMESPACE, $route, array_merge($this->getAdditionalRegistrationArgs(), [
                    'methods' => strtoupper($method),
                    'callback' => [$this, $method],
                    'permission_callback' => [$this, 'permissionCallback']
                ]));
            }
        }
    }

    /**
     * Additional arguments to be used when registering the endpoint.
     *
     * Override this method in child class if you want to pass additional parameters.
     *
     * @return array
     */
    public function getAdditionalRegistrationArgs()
    {
        return [];
    }

    /**
     * Callback to be fired on `get` request.
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function get(WP_REST_Request $request)
    {
    }

    /**
     * Callback to be fired on `post` request.
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function post(WP_REST_Request $request)
    {
    }

    /**
     * Callback to be fired on `put` request.
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function put(WP_REST_Request $request)
    {
    }

    /**
     * Callback to be fired on `patch` request.
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function patch(WP_REST_Request $request)
    {
    }

    /**
     * Callback to be fired on `delete` request.
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function delete(WP_REST_Request $request)
    {
    }

    /**
     * The permission callback to determine whether to allow or deny access to endpoint.
     *
     * Override this method in child class and return `true` to allow or `false` to deny access.
     *
     * Default: returns false.
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function permissionCallback(WP_REST_Request $request): bool
    {
        return false;
    }

    public function createResponse($data, $status_code = 200)
    {
        $response = new WP_REST_Response($data, $status_code);
        return $response;
    }

    public function error($message, $status_code = 500, $data = [])
    {
        $response = new WP_Error($status_code, $message, $data);
        return $response;
    }
}
