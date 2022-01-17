<?php

namespace Zeus\Models;

use Error;
use ReflectionClassConstant;
use WP_Post;
use WP_Query;

abstract class Post
{
    private $meta_updated = array();

    private $meta_before;

    /** @var array<string,array> */
    static $empty_meta_query = array();

    public function __construct(int $id = 0)
    {
        $this->meta_values = array();

        if ($id > 0) {
            $post_type = static::get_post_type();
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
     * Compila os labels para o Wordpress durante o registro do post type.
     *
     * @param mixed $plural Nome plural do post type
     * @param mixed $singular Nome singular do post type
     * @param string $x "o" para gênero masculino, "a" para gênero feminino
     * @return array
     */
    public static function compile_labels($plural, $singular, $x = "o")
    {
        return [
            'name' => $plural,
            'singular_name' => $singular,
            'add_new_item' => "Adicionar nov$x $singular",
            'edit_item' => "Editar $singular",
            'view_item' => "Visualizar $singular",
            'view_items' => "Visualizar $plural",
            'search_items' => "Buscar $plural",
            'not_found' => "Não há nenhum" . (($x == 'o') ? '' : 'a') . " $singular.",
            'not_found_in_trash' => "Nenhum" . (($x == 'o') ? '' : 'a') . " $singular encontrado na lixeira",
            // 'parent_item_colon' => "Parent $singular",
            'all_items' => "Tod" . $x . "s " . $x . "s $plural",
            'archives' => "$plural",
            'attributes' => "Atributos d$x $singular",
            'insert_into_item' => "Inserir n$x $singular",
            // 'uploaded_to_this_item' => "Upload ao $singular realizado",
        ];
    }

    /**
     * Cria um post no banco de dados e retorna o objeto `CustomPostType` correspondente.
     *
     * @param string $post_title Título do post
     * @param array $meta_input Array com metadados do post
     * @param string $post_status Status do post (padrão: publicado)
     * @return static
     */
    public static function add(string $post_title, array $meta_input = [], string $post_status = 'publish', array $args = [])
    {
        $add = array_merge(
            $args,
            array(
                'post_status' => $post_status,
                'post_type' => static::get_post_type(),
                'post_title' => $post_title,
                'meta_input' => $meta_input
            )
        );
        return static::get(wp_insert_post($add));
    }

    /**
     * Salva os meta dados alterados do post.
     *
     * @return void
     */
    public function save_meta($create_note = true)
    {
        if (isset($this->id)) {
            // QueryContainer::insert_post_note($this->id, "Meta dados da lista de compra atualizados: " . print_r($this->, true));
            $note = "Meta dados atualizados:";
            $has_updates = false;
            foreach ($this->meta_updated as $meta_key) {
                if (
                    !isset($this->meta_before[$meta_key]) || ($this->meta_before[$meta_key] != $this->meta_values[$meta_key])
                    && (!is_array($this->meta_before[$meta_key])
                        || !is_array($this->meta_values[$meta_key])
                        || !empty(array_diff($this->meta_before[$meta_key], $this->meta_values[$meta_key]))
                        || !empty(array_diff($this->meta_values[$meta_key], $this->meta_before[$meta_key])))
                ) {
                    $has_updates = true;
                    $note .= "\n   {$meta_key}: " . print_r($this->meta_values[$meta_key], true);
                    update_post_meta($this->id, $meta_key, $this->meta_values[$meta_key]);
                }
            }
            if ($create_note && $has_updates) {
                Database::insert_post_note($this->id, $note);
            }
            $this->meta_updated = array();

            $this->meta_before = $this->meta_values;
        }
    }

    /**
     * Retorna o WP_Post correspondente.
     *
     * @return WP_Post
     */
    public function get_post(): WP_Post
    {
        if (!isset($this->post)) {
            $this->post = get_post($this->id);
        }
        return $this->post;
    }

    /**
     * Obtém um meta_value do post. Retorna `null` caso o meta_key não exista.
     *
     * @param string|null $key meta_key que deseja. Caso não seja informado, retornará um array com todos os metadados.
     * @return mixed|null
     */
    public function get_meta(string $key = null)
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
     * Adiciona uma alteração nos metadados do post.
     *
     * @param string $key meta_key a ser alterado
     * @param mixed $value meta_value
     * @param bool $save_immediately Se deve armazenar imediatamente no banco de dados. Para salvar posteriormente, utilize `->save_meta()`.
     * @return void
     */
    public function update_meta(string $key, $value, $save_immediately = false)
    {
        $this->meta_values[$key] = $value;
        if (!in_array($key, $this->meta_updated)) {
            $this->meta_updated[] = $key;
        }
        if ($save_immediately) {
            $this->save_meta();
        }
    }

    /**
     * Verifica se o post tem alterações pendentes.
     *
     * @return bool
     */
    public function has_unsaved_changes()
    {
        return count($this->meta_updated) > 0;
    }

    /**
     * Obtém o ID do post.
     *
     * @return mixed
     */
    public function get_id()
    {
        return $this->id;
    }


    /**
     * Função para gerar o register_post_type no WP de acordo com o Custom Post Type.
     * Deverá executar a função do wordpress `register_post_type`.
     *
     * @return void
     */
    abstract static function register_post_type();

    /**
     * Função executada ao salvar o post no painel admin.
     *
     * @param mixed $post_id
     * @return mixed
     */
    abstract static function on_post_save($post_id);

    /**
     * Renderiza o metabox.
     *
     * @deprecated
     *
     * @return void
     */
    public static function render_metabox()
    {
        $class = array_reverse(explode('\\', get_class(new static())))[0];
        call_user_func(array('Oinb\\PostTypes\\Views\\' . $class, 'render'));
    }

    /**
     * Registra o metabox.
     *
     * @deprecated
     *
     * @return void
     */
    public static function register_metabox()
    {
        add_meta_box(
            static::get_post_type() . '_add_fields',
            __('Meta dados'),
            array(static::class, 'render_metabox'),
            static::get_post_type()
        );
    }

    /**
     * Consulta as classes em `Oinb\PostTypes` e executa individualmente a função `register_post_type`
     * de cada post-type.
     *
     * @return void
     * @throws Exception
     */
    public static function register_post_types()
    {
        $classes = ClassFinder::getClassesInNamespace('Oinb\\PostTypes');

        foreach ($classes as $class) {
            call_user_func(array($class, 'register_post_type'));
            $post_type = constant($class . '::POST_TYPE');
            add_action('save_post_' . $post_type, array($class, 'on_post_save'));
        }

        // print_r($classes);
    }


    /**
     * Obtém os dados do item em formato array.
     * @return array
     */
    public function get_data()
    {
        $post = $this->get_post();
        $data = (array)$post;

        $return = array(
            "meta_data" => $this->get_meta()
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

    public function get_changed_meta()
    {
        return $this->meta_updated;
    }

    public static function get_post_type()
    {
        try {
            $post_type = new ReflectionClassConstant(static::class, 'POST_TYPE');
            return $post_type->getValue();
        } catch (\Throwable $th) {
            return '';
        }
    }

    public static function get_archive_link()
    {
        return get_post_type_archive_link(static::get_post_type());
    }
    /**
     * Obtém os campos do PostType
     *
     * @return FormField[]
     */
    public static function get_fields()
    {
        try {
            $post_fields = new ReflectionClassConstant(static::class, 'POST_FIELDS');
            return array_map(function ($post_field_args) {
                return new FormField($post_field_args);
            }, $post_fields->getValue());
        } catch (\Throwable $th) {
            return array();
        }
    }

    function create_form()
    {
        return new Form(self::get_fields(), $this->get_meta());
    }

    /**
     * Performa uma WP_Query e retorna o array de objetos CPT.
     * A função toma conta de informar o parâmetro 'post_type' e
     * também executa `wp_reset_query()`.
     *
     * @param mixed $args
     * @return array<static>
     */
    public static function wp_query($args = array(), ...$post_type_args)
    {

        $original_global_post = $GLOBALS['post'];

        $post_type = static::get_post_type();
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

    static function get_items_as_options($query = array())
    {
        $items = static::wp_query($query);

        return array_map(function (self $item) {
            $post_type = static::get_post_type();
            return apply_filters("oinb_{$post_type}_get_items_as_options", array(
                'value' => $item->get_id(),
                'label' => $item->get_post()->post_title
            ));
        }, $items);
    }

    static function register_fields_metabox()
    {
        add_action('admin_menu', function () {
            remove_meta_box('postcustom', static::get_post_type(), 'normal'); //removes custom fields for page
            remove_meta_box('commentstatusdiv', static::get_post_type(), 'normal'); //removes comments status for page
            remove_meta_box('commentsdiv', static::get_post_type(), 'normal'); //removes comments for page
            remove_meta_box('authordiv', static::get_post_type(), 'normal'); //removes author for page
        });
        add_action('add_meta_boxes', function () {
            add_meta_box(static::get_post_type() . '_add_fields', "Campos de " . static::get_post_type(), function () {
                $item = static::get(get_the_ID());
                $item->create_form()->render();
            }, static::get_post_type());
        });
        add_action('save_post_' . static::get_post_type(), function ($post_id) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if ($parent_id = wp_is_post_revision($post_id)) {
                $post_id = $parent_id;
            }
            /** @var string[] */
            $fields = array_map(function (FormField $field) {
                return $field->get_name();
            }, static::get_fields());

            $item = static::get($post_id);
            foreach ($fields as $field) {
                if (array_key_exists($field, $_POST)) {
                    // Verifica se tem campo Array ou não e aplica o sanitize correto
                    if (is_array($_POST[$field])) {
                        $sanitize_post_field = filter_var_array($_POST[$field], FILTER_VALIDATE_INT);
                    } else {
                        $sanitize_post_field = sanitize_text_field($_POST[$field]);
                    }
                    $item->update_meta($field, $sanitize_post_field);
                    //update_post_meta($post_id, $field, $sanitize_post_field);
                }
            }
            $item->save_meta();
        });
    }

    static function register_post_status($slug, $label, $icon = "info-outline")
    {
        register_post_status("oinb-$slug", array(
            'label'                     => $label,
            'label_count'               => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'plugin-domain'),
            'public'                    => true,
            'post_type'                 => array(static::get_post_type()), // Define one or more post types the status can be applied to.
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'show_in_metabox_dropdown'  => true,
            'show_in_inline_dropdown'   => true,
            'dashicon' => "dashicons-$icon"
        ));
    }

    function update_status($status)
    {
        wp_update_post(array(
            'post_status' => "oinb-$status",
            'ID' => $this->get_id()
        ));
    }

    function get_post_status()
    {
        return $this->get_post()->post_status;
    }

    function get_status()
    {
        $status = $this->get_post_status();
        if (function_exists('wp_statuses_get')) {
            $status = wp_statuses_get($status)->label;
        }
        return $status;
    }
}
