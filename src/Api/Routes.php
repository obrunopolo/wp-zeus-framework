<?php

namespace Zeus\Api;

use Zeus\Framework\RoutesFramework;

class Routes extends RoutesFramework
{

    // Add endpoint classes to this array
    const ENDPOINTS = [
        "zeus/v1" => [
            "users" => \Zeus\Api\Examples\Users::class,

        ],

    ];
}
