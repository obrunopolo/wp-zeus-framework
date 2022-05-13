<?php

namespace Zeus\Api\Examples;

use WP_REST_Request;
use Zeus\Framework\Contracts\Endpoint;

class Users extends Endpoint
{

    public function permissionCallback(WP_REST_Request $request): bool
    {
        return true;
    }

    public function get(WP_REST_Request $request)
    {
        return $this->response([
            "users" => [
                [
                    "id" => 1,
                    "name" => "Jon Snow",
                    "address" => "Somewhere"
                ],
                [
                    "id" => 2,
                    "name" => "Aragorn",
                    "address" => "Somehow"
                ]
            ]
        ]);
    }
}
