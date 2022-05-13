<?php

namespace Zeus\Framework\Contracts;

use Error;
use ReflectionClassConstant;
use WP_Post;
use WP_Query;
use Zeus\Views\Components\Form;
use Zeus\Views\Components\FormField;

abstract class Post
{

    const CPT_STATUS_PREFIX = "zeus-";

    static $objects = [];

    private $meta_values = [];

    private $meta_updated = [];

    private $meta_before;

    public function __construct(int $id = 0)
    {
        $this->meta_values = [];

        if ($id > 0) {
            $post_type = static::getPostType();
            if (get_post_type($id) !== $post_type) {
                throw new Error('[CustomPostType] post ' . $id . ' is not ' . $post_type);
            }
            $this->id = $id;
            // $this->post = get_post($id);

            $raw_post_meta = get_post_meta($id);

            array_walk($raw_post_meta, function ($value, $key) {
                if (is_array($value) && count($value) === 1) {
                    $this->meta_values[$key] = @maybe_unserialize($value[0]);
                } else {
                    $this->meta_values[$key] = $value;
                }
            });

            $this->meta_before = $this->meta_values;
        }
    }

    /**
     * Register post type.
     *
     * This function should be implemented by child class and call
     * `register_post_type` inside its body.
     *
     * Is executed during `zeus_register_post_types` hook.
     *
     * @return void
     */
    abstract static function registerPostType();

    /**
     * Fetches a post from the database.
     *
     * @param int $id
     *
     * @return static
     */
    static function get($id, ...$args)
    {
        if (!isset(static::$objects[$id])) {
            static::$objects[$id] = new static($id, ...$args);
        }
        return static::$objects[$id];
    }

    /**
     * Create a post.
     *
     * @param string $args The args to be passed to the post. See `wp_insert_post` documentation.
     *
     * @return static The `Post` instance
     */
    public static function create(array $args = [])
    {
        $add = array_merge(
            $args,
            array(
                'post_type' => static::getPostType(),
            )
        );
        return static::get(wp_insert_post($add));
    }

    /**
     * Deletes a post. Fails if the post does not exist or is from another post type.
     *
     * @param int $id The post ID to be deleted.
     * @param bool $force_delete Optional. Whether to bypass Trash and force deletion. Default false.
     * @return WP_Post|false|null
     * @throws Error
     */
    public static function delete($id, $force_delete = false)
    {
        if (get_post_type($id) !== static::getPostType()) {
            throw new Error('[CustomPostType] post ' . $id . ' is not ' . static::getPostType());
        }
        return wp_delete_post($id, $force_delete);
    }


    /**
     * This function hooks to the post save on admin panel, and should be
     * overriden by the child class if a custom interface is provided.
     *
     * @param mixed $post_id
     * @return mixed
     */
    public static function onPostSave($post_id)
    {
        // to implement in child class
    }


    /**
     * Gets post_type value for this class.
     *
     * @return mixed
     */
    public static function getPostType()
    {
        try {
            $post_type = new ReflectionClassConstant(static::class, 'POST_TYPE');
            return $post_type->getValue();
        } catch (\Throwable $th) {
            return '';
        }
    }

    /**
     * Gets archive link for post type.
     *
     * @return string|false
     */
    public static function getArchiveLink()
    {
        return get_post_type_archive_link(static::getPostType());
    }

    /**
     * Gets post form fields, if provided.
     *
     * @return FormField[]
     */
    public static function getFormFields()
    {
        try {
            $post_fields = static::getFields();
            return array_map(function ($post_field_args) {
                return new FormField($post_field_args);
            }, $post_fields);
        } catch (\Throwable $th) {
            return [];
        }
    }

    /**
     * Gets the post type fields array. Override this method on child class to
     * generate fields in admin area.
     *
     * @return array
     */
    public static function getFields()
    {
        return [];
    }

    /**
     * Runs WP_Query for this post type. It does not require
     * to inform 'post_type' field, cleans the global object
     * and runs `wp_reset_query` after the query is completed.
     *
     * @param mixed $args
     * @return array<static> Array of `Post` objects.
     */
    public static function wpQuery($args = [], ...$post_type_args)
    {

        $original_global_post = $GLOBALS['post'];

        $post_type = static::getPostType();
        $args = array_merge(
            array(
                'posts_per_page' => -1,
            ),
            $args,
            array(
                'post_type' => $post_type,
                'fields' => 'ids'
            )
        );

        $query = new WP_Query($args);

        $map = array_map(function ($id) use ($post_type_args) {
            return static::get($id, ...$post_type_args);
        }, $query->get_posts());

        // wp_reset_query();
        wp_reset_postdata();

        setup_postdata($GLOBALS['post'] = &$original_global_post);

        return $map;
    }

    /**
     * Runs a WP_query and returns the results as an
     * array of items with `value` and `label`.
     *
     * Should be used to create `<option>` html objects.
     *
     * @param array $query
     * @return static[]
     */
    static function getAsHtmlOptions($query = [])
    {
        $items = static::wpQuery($query);

        return array_map(function (self $item) {
            $post_type = static::getPostType();
            return apply_filters("zeus_{$post_type}_get_items_as_options", array(
                'value' => $item->getId(),
                'label' => $item->getWpPost()->post_title
            ));
        }, $items);
    }

    /**
     * Registers meta box in admin area, to render
     * the contents of `getFields` method as a
     * form.
     *
     * Should be called inside `registerPostType` method if
     * `getFields` is provided.
     *
     * @return void
     */
    static function registerFieldsMetabox()
    {
        add_action('admin_menu', function () {
            remove_meta_box('postcustom', static::getPostType(), 'normal'); //removes custom fields for page
        });
        add_action('add_meta_boxes', function () {
            $post_type = static::getPostType();
            add_meta_box($post_type . '_post_fields_metabox', sprintf(esc_html__("%s fields", "zeus-framework"), $post_type), function () {
                $item = static::get(get_the_ID());
                $item->createForm()->render();
            }, $post_type);
        });
        add_action('save_post_' . static::getPostType(), function ($post_id) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if ($parent_id = wp_is_post_revision($post_id)) {
                $post_id = $parent_id;
            }
            /** @var string[] */
            $fields = array_map(function (FormField $field) {
                return $field->getName();
            }, static::getFormFields());

            $item = static::get($post_id);
            foreach ($fields as $field) {
                if (array_key_exists($field, $_POST)) {
                    // Verifica se tem campo Array ou não e aplica o sanitize correto
                    if (is_array($_POST[$field])) {
                        $sanitize_post_field = filter_var_array($_POST[$field], FILTER_VALIDATE_INT);
                    } else {
                        $sanitize_post_field = sanitize_text_field($_POST[$field]);
                    }
                    $item->updateMeta($field, $sanitize_post_field);
                }
            }
            $item->save();
        });
    }

    /**
     * Registers a post status for the post type.
     *
     * Should be called inside `registerPostType` method.
     *
     * @param mixed $slug The post status slug, without the prefix.
     * @param mixed $label The name of the status to be displayed.
     * @param string $icon The icon used on the status. See dashicons library for more information.
     * @return void
     */
    static function registerPostStatus($slug, $label, $icon = "info-outline")
    {
        register_post_status(self::CPT_STATUS_PREFIX . $slug, array(
            'label'                     => $label,
            'label_count'               => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'plugin-domain'),
            'public'                    => true,
            'post_type'                 => array(static::getPostType()), // Define one or more post types the status can be applied to.
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'show_in_metabox_dropdown'  => true,
            'show_in_inline_dropdown'   => true,
            'dashicon' => "dashicons-$icon"
        ));
    }

    /**
     * Saves post to database.
     *
     * @return void
     */
    public function save()
    {
        if (isset($this->id)) {
            foreach ($this->meta_updated as $meta_key) {
                if (
                    !isset($this->meta_before[$meta_key]) || ($this->meta_before[$meta_key] != $this->meta_values[$meta_key])
                    && (!is_array($this->meta_before[$meta_key])
                        || !is_array($this->meta_values[$meta_key])
                        || !empty(array_diff($this->meta_before[$meta_key], $this->meta_values[$meta_key]))
                        || !empty(array_diff($this->meta_values[$meta_key], $this->meta_before[$meta_key])))
                ) {

                    update_post_meta($this->id, $meta_key, $this->meta_values[$meta_key]);
                }
            }

            $this->meta_updated = [];

            $this->meta_before = $this->meta_values;
        }
    }

    /**
     * Gets `WP_Post` object.
     *
     * @return WP_Post
     */
    public function getWpPost(): WP_Post
    {
        if (!isset($this->post)) {
            $this->post = get_post($this->id);
        }
        return $this->post;
    }

    /**
     * Gets post meta data by key. Return null if key does not exist.
     *
     * @param string|null $key Key to be retrieved. If ommited, returns an array containing all meta data.
     * @return mixed|null
     */
    public function getMeta(string $key = null)
    {
        if (is_null($key)) {
            return $this->meta_values;
        }
        // print_r($this->meta_values);
        if (!isset($this->meta_values[$key])) {
            return null;
        }
        return $this->meta_values[$key];
    }

    /**
     * Updates meta data. You need to call `save` method or set `$save_immediately`
     * argument to true to persist changes.
     *
     * @param string $key Meta key to be updated
     * @param mixed $value The value
     * @param bool $save_immediately Set it to true to save changes afterwards.
     * @return void
     */
    public function updateMeta(string $key, $value, $save_immediately = false)
    {
        $this->meta_values[$key] = $value;
        if (!in_array($key, $this->meta_updated)) {
            $this->meta_updated[] = $key;
        }
        if ($save_immediately) {
            $this->save();
        }
    }

    /**
     * Returns true if post has pending changes.
     *
     * @return bool
     */
    public function hasChanges()
    {
        return count($this->meta_updated) > 0;
    }

    /**
     * Gets post ID.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets an array with post data.
     *
     * @return array
     */
    public function getData()
    {
        $post = $this->getWpPost();
        $data = (array)$post;

        $return = array(
            "meta_data" => $this->getMeta()
        );

        foreach ($data as $key => $value) {
            if (strpos($key, 'post_') === 0) {
                $return[substr($key, 5)] = $value;
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * Gets all items updated, but not saved.
     *
     * @return array
     */
    public function getUnsavedChanges()
    {
        return $this->meta_updated;
    }

    /**
     * Creates a form with the fields of post type and the values of the post instance.
     *
     * @return Form
     */
    function createForm()
    {
        return new Form(self::getFormFields(), $this->getMeta());
    }


    /**
     * Updates the post status.
     *
     * @param mixed $status
     * @return void
     */
    function updateStatus($status)
    {
        $prefix = "";
        // Check if not wp default status
        if (!in_array($status, ["publish", "draft", "trash"])) {
            $prefix = self::CPT_STATUS_PREFIX;
        }
        wp_update_post(array(
            'post_status' => $prefix . $status,
            'ID' => $this->getId()
        ));
    }

    /**
     * Gets the post status slug.
     *
     * @return string
     */
    function getStatusSlug()
    {
        return $this->getWpPost()->post_status;
    }

    /**
     * Gets the post status.
     *
     * @return mixed
     */
    function getStatus()
    {
        $status = $this->getStatusSlug();
        if (function_exists('wp_statuses_get')) {
            $status = call_user_func('wp_statuses_get', $status)->label;
        }
        return $status;
    }
}
