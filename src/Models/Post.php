<?php

namespace Zeus\Models;

use Error;
use ReflectionClassConstant;
use WP_Post;
use WP_Query;
use Zeus\Views\Components\Form;
use Zeus\Views\Components\FormField;

abstract class Post
{

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
     * Register post type.
     *
     * This function should be implemented by child class and call
     * `register_post_type` inside its body.
     *
     * Is executed during `register_post_types` hook.
     *
     * @return void
     */
    abstract static function registerPostType();

    /**
     * Função executada ao salvar o post no painel admin.
     *
     * @param mixed $post_id
     * @return mixed
     */
    public static function onPostSave($post_id)
    {
        // to implement in child class
    }


    // public static function register_post_types()
    // {
    //     $classes = ClassFinder::getClassesInNamespace('Oinb\\PostTypes');

    //     foreach ($classes as $class) {
    //         call_user_func(array($class, 'register_post_type'));
    //         $post_type = constant($class . '::POST_TYPE');
    //         add_action('save_post_' . $post_type, array($class, 'on_post_save'));
    //     }

    //     // print_r($classes);
    // }


    /**
     * Obtém os dados do item em formato array.
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

    public function getUnsavedChanges()
    {
        return $this->meta_updated;
    }

    public static function getPostType()
    {
        try {
            $post_type = new ReflectionClassConstant(static::class, 'POST_TYPE');
            return $post_type->getValue();
        } catch (\Throwable $th) {
            return '';
        }
    }

    public static function getArchiveLink()
    {
        return get_post_type_archive_link(static::getPostType());
    }
    /**
     * Obtém os campos do PostType
     *
     * @return FormField[]
     */
    public static function getFields()
    {
        try {
            $post_fields = new ReflectionClassConstant(static::class, 'POST_FIELDS');
            return array_map(function ($post_field_args) {
                return new FormField($post_field_args);
            }, $post_fields->getValue());
        } catch (\Throwable $th) {
            return [];
        }
    }

    function createForm()
    {
        return new Form(self::getFields(), $this->getMeta());
    }

    /**
     * Performa uma WP_Query e retorna o array de objetos CPT.
     * A função toma conta de informar o parâmetro 'post_type' e
     * também executa `wp_reset_query()`.
     *
     * @param mixed $args
     * @return array<static>
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

    static function registerFieldsMetabox()
    {
        add_action('admin_menu', function () {
            remove_meta_box('postcustom', static::getPostType(), 'normal'); //removes custom fields for page
            remove_meta_box('commentstatusdiv', static::getPostType(), 'normal'); //removes comments status for page
            remove_meta_box('commentsdiv', static::getPostType(), 'normal'); //removes comments for page
            remove_meta_box('authordiv', static::getPostType(), 'normal'); //removes author for page
        });
        add_action('add_meta_boxes', function () {
            add_meta_box(static::getPostType() . '_add_fields', "Campos de " . static::getPostType(), function () {
                $item = static::get(get_the_ID());
                $item->createForm()->render();
            }, static::getPostType());
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
            }, static::getFields());

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
                    //update_post_meta($post_id, $field, $sanitize_post_field);
                }
            }
            $item->save();
        });
    }

    static function registerPostStatus($slug, $label, $icon = "info-outline")
    {
        register_post_status("oinb-$slug", array(
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

    function updateStatus($status)
    {
        wp_update_post(array(
            'post_status' => "oinb-$status",
            'ID' => $this->getId()
        ));
    }

    function getPostStatus()
    {
        return $this->getWpPost()->post_status;
    }

    function getStatus()
    {
        $status = $this->getPostStatus();
        if (function_exists('wp_statuses_get')) {
            $status = call_user_func('wp_statuses_get', $status)->label;
        }
        return $status;
    }
}
