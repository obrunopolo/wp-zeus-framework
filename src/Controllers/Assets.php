<?php

namespace Zeus\Controllers;

use Zeus\Models\Extensions\Controller;
use Masterminds\HTML5;

class Assets implements Controller
{

    use \Zeus\Models\Extensions\Singleton;

    const OPTION_JS_ENTRIES = "zeus_js_entries";
    const ASSETS_PREFIX = "zeus-";

    private function readFileDependencies($filename, &$array)
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

    /**
     * Updates the file references for JavaScript built with Webpack.
     *
     * @return void
     */
    public function updateAssets()
    {
        $files = json_decode(file_get_contents(ZEUS_ABSPATH . "/lib/ts/entries.json"));

        $entries = [];
        foreach ($files->entries as $entry) {
            $this->readFileDependencies($entry, $entries);
        }

        update_option(self::OPTION_JS_ENTRIES, $entries, true);
    }

    public function enqueueScripts()
    {
        // Uncomment if you will use react

        // wp_enqueue_script('react');
        // wp_enqueue_script('react-dom');

        // Enqueues JavaScript built with Webpack
        $entries = get_option(self::OPTION_JS_ENTRIES, []);

        foreach ($entries as $name => $data) {
            foreach ($data["imports"] as $item) {
                if (is_dev()) {
                    $path = $item;
                    $version = false;
                } else {
                    [$path, $version] = explode("?ver=", $item);
                }
                if (apply_filters("zeus_enqueues_{$name}", true)) {
                    wp_enqueue_script(self::ASSETS_PREFIX . $name, $path, [], $version);
                }
            }
        }
    }

    public function run()
    {

        // Determine when JS should be loaded.
        add_action("zeus_enqueues_helloworld", "__return_true");

        add_action("wp_enqueue_scripts", [$this, "enqueueScripts"]);
        add_action("zeus_deploy", [$this, "updateAssets"]);

        if (ZEUS_DISABLE_AUTODEPLOY === false) {
            do_action('zeus_deploy');
        }
    }
}
