<?php

namespace Zeus\Framework;

use Zeus\Framework\Contracts\Controller;
use Zeus\Framework\Contracts\Singleton;
use Masterminds\HTML5;

class AssetsFramework extends Singleton implements Controller
{

    const OPTION_JS_ENTRIES = "zeus_js_entries";
    const OPTION_CSS_ENTRIES = "zeus_css_entries";
    const OPTION_LAST_VERSION = "zeus_assets_last_version";

    const ASSETS_PREFIX = "zeus-";

    private $variables = [];


    /**
     * Reads one entry from JS files and adds the load parameters to the `$array` variable.
     *
     * @param mixed $filename The entrypoint file name.
     * @param mixed $array The array the entry should be added to.
     * @return void
     */
    private function readJsDependencies($filename, &$array)
    {
        [$file, $_extension] = explode(".", $filename);
        $array[$file] = [
            'imports' => [],
        ];
        if (is_dev()) {
            $array[$file]["imports"] = [
                ZEUS_URL . "/includes/js/{$file}.bundle.js"
            ];
        } else {
            $content = file_get_contents(ZEUS_URL . "/includes/js/{$file}-scripts.html");
            $elements = [];
            $html5 = new HTML5();
            $dom = $html5->loadHTML($content);
            $query = $dom->getElementsByTagName('script');
            $count = $query->count();

            for ($i = 0; $i < $count; $i++) {
                $element = $query->item($i);
                if ($element) {
                    $src = $element->attributes->getNamedItem("src")->nodeValue;

                    $bundle_name = str_replace("/..", "", $src);
                    $file_content = file_get_contents(ZEUS_URL . $bundle_name);

                    $content_hash = md5($file_content);

                    $elements[] = ZEUS_URL . "{$bundle_name}?ver={$content_hash}";
                }
            }

            $array[$file]["imports"] = $elements;
        }
    }

    private function readCssToUrl($filename, &$array)
    {
        [$file, $_extension] = explode(".", $filename);
        $content = file_get_contents(ZEUS_ABSPATH . "/includes/css/" . $file . ".css");
        $version = md5($content);
        $array[$file] = ZEUS_URL . "/includes/css/" . $file . ".css?ver=" . $version;
    }

    /**
     * Updates the file references for JavaScript built with Webpack.
     *
     * Should be executed when JS has changed.
     *
     * @return void
     */
    public function updateAssets()
    {
        $js = json_decode(file_get_contents(ZEUS_ABSPATH . "/includes/js/entries.json"));
        $css = json_decode(file_get_contents(ZEUS_ABSPATH . "/includes/css/entries.json"));

        $js_entries = [];
        $css_entries = [];
        foreach ($js->entries as $entry) {
            $this->readJsDependencies($entry, $js_entries);
        }

        foreach ($css->entries as $entry) {
            $this->readCssToUrl($entry, $css_entries);
        }

        update_option(self::OPTION_JS_ENTRIES, $js_entries, true);
        update_option(self::OPTION_CSS_ENTRIES, $css_entries, true);
        update_option(self::OPTION_LAST_VERSION, zeus()->getVersion(), true);
    }

    public function enqueueScripts()
    {

        // Enqueues JavaScript built with Webpack
        $js = apply_filters("zeus_get_js_entries", get_option(self::OPTION_JS_ENTRIES, []));

        foreach ($js as $name => $data) {
            if (true === apply_filters("zeus_enqueues_js_{$name}", false)) {
                foreach ($data["imports"] as $item) {
                    if (is_dev()) {
                        $path = $item;
                        $version = false;
                    } else {
                        [$path, $version] = explode("?ver=", $item);
                    }
                    wp_enqueue_script(self::ASSETS_PREFIX . $name, $path, [], $version);
                    if (isset($this->variables[$name])) {
                        foreach ($this->variables[$name] as $var => $value) {
                            wp_localize_script(self::ASSETS_PREFIX . $name, $var, $value);
                        }
                    }
                }
            }
        }

        // Enqueues css built with node-sass
        $css = apply_filters("zeus_get_css_entries", get_option(self::OPTION_CSS_ENTRIES, []));

        foreach ($css as $name => $src) {
            if (true === apply_filters("zeus_enqueues_css_{$name}", false)) {
                if (is_dev()) {
                    $path = $src;
                    $version = false;
                } else {
                    [$path, $version] = explode("?ver=", $src);
                }
                wp_enqueue_style(self::ASSETS_PREFIX . $name, $path, [], $version);
            }
        }
    }


    /**
     * Adds a variable to be associated with an JavaScript file.
     *
     * @param string $entry_name The entry name, without file extension, that the variable will be added to.
     * @param string $var_name The name of the variable to be accessible in JavaScript.
     * @param array $value The value of the variable.
     * @return void
     */
    public function addVar($entry_name, $var_name, $value)
    {
        if (!isset($this->variables[$entry_name])) {
            $this->variables[$entry_name] = [];
        }
        $this->variables[$entry_name][$var_name] = $value;
    }

    function addAssetsActions()
    {
        // Enqueue files
        add_action("wp_enqueue_scripts", [$this, "enqueueScripts"], 20);

        // Renew file references
        add_action("zeus_deploy", [$this, "updateAssets"]);

        if (ZEUS_ALWAYS_CHECK_CHUNKS) {
            do_action("zeus_deploy");
        }

        if (ZEUS_ENABLE_AUTODEPLOY === true && get_option(self::OPTION_LAST_VERSION) !== zeus()->getVersion()) {
            add_action("shutdown", function () {
                if (!did_action("zeus_deploy")) {
                    do_action("zeus_deploy");
                }
            });
        }
    }

    function run()
    {

        // Determine when assets should be loaded.
        add_filter("zeus_enqueues_js_helloworld", "__return_true");
        add_filter("zeus_enqueues_css_helloworld", "__return_true");
    }
}
