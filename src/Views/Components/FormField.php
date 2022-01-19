<?php

namespace Zeus\Views\Components;

use Zeus\Models\Component;

class FormField extends Component
{

    // const INPUT_TYPE_STRING = 'string';
    private $label;

    private $options = array();

    private $selected = "";


    public function __construct(
        $args = array()
    ) {

        if (isset($args['meta_key'])) {
            if (!isset($args['name'])) {
                $args['name'] = $args['meta_key'];
            }
            if (!isset($args['id'])) {
                $args['id'] = $args['meta_key'];
            }
            unset($args['meta_key']);
        }

        if (isset($args['label'])) {
            $this->label = $args['label'];
            unset($args['label']);
        } else {
            $this->label = $args['name'];
        }

        if (!isset($args['type'])) {
            $args['type'] = "text";
        }

        if ($args['type'] == "select" || $args['type'] == "checkboxes") {
            $this->options = $args['options'];
            $this->selected = (isset($args['value']) ? $args['value'] : "");
            unset($args['options']);
            unset($args['value']);
        }

        parent::__construct($args);
    }

    public function getName()
    {
        return $this->props['name'];
    }

    public function setValue($value)
    {
        if ($this->props['type'] == "select" || $this->props['type'] == "checkboxes") {
            $this->selected = $value;
        } else {
            $this->props['value'] = $value;
        }
    }


    public function html(): string
    {
        if ($this->props['type'] == "hidden") return "";

        if ($this->props['type'] === "checkboxes") {

            $options = array();

            if (is_callable($this->options)) {
                //$options = $this->{"options"}();
                $options = call_user_func($this->options);
            } else if (is_array($this->options)) {

                $options = $this->options;
            }

            $index = -1;
            // print_r($this->selected);
            $html = '
            <label for="' . $this->props['id'] . '">' . $this->label . ':</label>
            <div style="display: flex; flex-direction: column; width: 100%;">' . implode("", array_map(function ($option) use (&$index) {
                $checked = '';

                if (in_array($option['value'], $this->selected)) {
                    $checked = 'checked';
                }
                $index++;
                return '<div><input type="checkbox" name="' . $this->props['name'] . '[]" id="' . $this->props['name'] . $index . '" value="' . $option['value'] . '" ' . $checked . ' /><label for="' . $this->props['name'] . $index . '">' . $option['label'] . '</label></div>';
            }, $options)) . '</div>
            ';
            return $html;
        }

        if ($this->props['type'] === "checkbox") {

            $props = $this->props;

            $value = $props['value'];

            if ($value == "yes" || $value == "on") {
                $props['checked'] = true;
            }
            unset($props['value']);

            return '<div style="display: flex; align-items: center;">' .
                '<input ' . get_html_element_attr($props) . ' />' .
                '<label for="' . $this->props['id'] . '">' . $this->label . ':</label>' .
                '</div>';
        }

        if (is_array($this->props['value'])) {
            return '<label for="' . $this->props['id'] . '">' . $this->label . ':</label>' . "<span>" . implode(", ", $this->props['value']) . "</span>";
        }

        if ($this->props['type'] == "select") {
            $options = array();
            $selected = $this->selected;
            if (is_callable($this->options)) {
                //$options = $this->{"options"}();
                $options = call_user_func($this->options);
            } else if (is_array($this->options)) {
                $options = $this->options;
            }


            return '
            <label for="' . $this->props['id'] . '">' . $this->label . ':</label>
            <select ' . get_html_element_attr($this->props) . '>
          ' . implode(array_map(function ($option) use ($selected) {
                // echo "$selected vs " . $option['value'];
                if ($selected == $option['value']) {
                    $option['selected'] = true;
                }

                $option_attr = get_html_element_attr($option);

                // print_r($option);
                return "<option {$option_attr}></option>";
            }, array_merge(array(
                array(
                    'value' => "",
                    'label' => ""
                )

            ), $options))) . '
            </select>';
        }
        return '
        <label for="' . $this->props['id'] . '">' . $this->label . ':</label>
        <input ' . get_html_element_attr($this->props) . ' />';
    }
}
