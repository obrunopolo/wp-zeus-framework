# Create REST API endpoints

To create a REST API endpoint, add a new class to the project:

 - `src/Controllers/Api/Examples/Post.php`

```php
<?php

namespace Zeus\Controllers\Api\Examples;

use Zeus\Models\Endpoint;
use WP_REST_Request;

class Post extends Endpoint
{

    const ROUTE = "post/(?P<id>[\d]+)";

    // Overriding permission callback, set to accept all requests
    public function permissionCallback(WP_REST_Request $request): bool
    {
        return true;
    }

    public function get(WP_REST_Request $request)
    {
        // callback on "GET" method

        $post = get_post($request["id"]);

        if (is_null($post)) {
            return $this->error(__('Post not found', 'zeus-framework'), 404);
        }

        return $this->createResponse($post->to_array());
    }

    public function post(WP_REST_Request $request)
    {
        // callback on "POST" method.

        $post = get_post($request["id"]);

        if (is_null($post)) {
            return $this->error(__('Post not found', 'zeus-framework'), 404);
        }

        // ... update the post data using `$request` values

        return $this->createResponse([
            'foo' => 'bar',
        ]);

    }

    public function delete(WP_REST_Request $request)
    {

        // callback on "DELETE" method.

        $post = get_post($request["id"]);

        if (is_null($post)) {
            return $this->error(__('Post not found', 'zeus-framework'), 404);
        }

        $data = wp_delete_post($request["id"]);

        if (is_null($data) || false === $data) {
            return $this->error(__('Error trying to delete post.', 'zeus-framework'), 500);
        }

        return $this->createResponse([
            'foo' => 'bar'
        ]);
    }
}

```

After creating the file, add it to the endpoint index at main `App` class:

 - `src/App.php`
```php
<?php

class App {

    // {{...}}
    public function registerRestEndpoints()
    {
        // Add endpoint classes to this array
        $endpoints = [
            "Examples\\Post"
        ];

        // Change this to match the root namespace where endpoints are created
        $namespace = "\\Zeus\\Controllers\\Api";

        // {{...}}
    }
    // {{...}}
}
```

Things to keep in mind:

 - An `Endpoint` child class may or may not implement the methods `get`, `post`, `put`, `patch` and `delete`.
 - If a request is made to the specified route using a method that was not implemented, then 404 is returned.
 - The permission callback in the example returns true. This should only be used in special cases. Always check if the user can access the requested resource.
 - If you do not supply the permission callback, it will return `false` by default and 403 (Forbidden) status is returned.
