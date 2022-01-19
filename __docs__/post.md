
# Zeus Post API

The post API covers the post registration and operations with posts of the registered type.

## Registering a Post Type

To register a new post type, you should create a class that extends `Zeus\Models\Post`. To illustrate, let's make a `Ticket` post type, which will have some logic of its own:

 - `src/Models/Post/Ticket.php`

```php
<?php

namespace Zeus\Models\Post;

use Zeus\Models\Post;

class Ticket extends Post {

    // this is where you define a slug to the post type.
    // must be unique across the post types.
    const POST_TYPE = 'ticket';

    // this is where you define the post fields to be displayed
    // in the admin area. See the Form API documentation.
    const POST_FIELDS = [
        [
            'meta_key' => 'amount',
            'label' => __('Amount', 'zeus-framework'),
        ],
        [
            'meta_key' => 'movie',
            'label' => 'Movie',
            'type' => 'select',
            'options' => [
                [
                    'value' => '15',
                    'label' => 'Star Wars - Episode I'
                ],
                [
                    'value' => '32',
                    'label' => 'Star Wars - Episode II'
                ],
                [
                    'value' => '7',
                    'label' => 'Star Wars - Episode III'
                ],
            ]
        ]
    ];

    // It is mandatory to implement this function
    // in every Post class.
    static function registerPostType()
    {
        // Call wordpress post type registration function
        // See `register_post_type` documentation for list of arguments.
        register_post_type([
            'public'          => true,
            'capability_type' => 'post',
            'rewrite'         => array('slug' => self::POST_TYPE),
            'menu_position'   => 8,
            'menu_icon'       => (version_compare($GLOBALS['wp_version'], '3.8', '>=')) ? 'dashicons-tickets-alt' : false,
            'has_archive'     => true,
            'publicly_queryable'  => false,
            'supports'        => ['title', 'thumbnail', 'custom-fields'],
        ]);

        // registers `POST_FIELDS` to be displayed in admin
        self::registerFieldsMetabox();
    }


    // Now you can create post specific functions
    // Basic getters and setters for meta data:

    public function getAmount()
    {
        return floatval($this->getMeta('amount'));
    }

    public function setAmount($value, $save_immediately)
    {
        $this->updateMeta('amount', $value, $save_immediately);
    }

    public function getMovieId()
    {
        return intval($this->getMeta('movie'));
    }

    public function setMovieId($value, $save_immediately)
    {
        $this->updateMeta('movie', $value, $save_immediately);
    }

}

```

Now, we need to add the class `Tickets` to `registerPostTypes` method in the main class `App`:

 - `src/App.php`

```php
<?php

class App
{

    // {{...}}

    public function registerPostTypes()
    {
        // Add post classes to this array.
        $post_types = [
            "Ticket"
        ];

        // Change this to match the folder where post types are created.
        $namespace = "\Zeus\Models\Post";

        // {{...}}
    }

}

```

The post type should now be registered.

## Post type operations

The class we just created is CRUD ready.

### Create

To create a post, we need to call the static method `create`:

```php
<?php

use Zeus\Models\Post\Ticket;

/** @var Ticket **/
$ticket = Ticket::create([
    'post_status' => 'publish',
    'post_title' => 'My new ticket',
    'meta_input' => [
        'amount' => '15.00',
        'movie' => '15'
    ]
]);

```

> In all `Post` methods, you can ommit the `post_type` parameter, since its defined by the child class.

### Read

There are two ways to read posts. To read a single post based on its ID, use the `get` static method:

```php
/** @var Ticket **/
$ticket = Ticket::get($the_post_id);
```

We can also run a `WP_Query` and loop through the results:

```php
/** @var Ticket[] **/
$tickets = Ticket::wpQuery($query_args);
// Loop through the tickets
foreach ($tickets as $ticket) {
    // Do something with the tickets
    echo "Ticket #" . $ticket->getId() . " - Total: " . $ticket->getAmount();
}
```

### Update

To update a post, you need a `Post` instance fetched with `get` or `wpQuery` methods.

```php
<?php
$ticket->updateStatus('draft');
$ticket->updateMeta('meta-to-update', $value);
$ticket->save();
```

### Delete

To delete a post, call the `delete` static method, using the post ID.

```php
<?php

Ticket::delete($the_post_id);
```
