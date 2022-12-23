<?php

namespace Zeus\Framework\Components;

use Zeus\Framework\Contracts\Component;

class Form extends Component
{

    /** @var FormField[] */
    public $fields;

    /** @var array */
    public $values;

    /**
     *
     * @param FormField[] $fields
     * @param array $values
     * @return void
     */
    public function __construct(
        $fields,
        $values
    ) {


        foreach ($values as $key => $value) {
            $find_fields = array_filter($fields, function (FormField $field) use ($key) {
                return $field->getName() == $key;
            });
            foreach ($find_fields as $field) {
                $field->setValue($value);
            }
        }

        $this->fields = $fields;

        parent::__construct();
    }


    public function html(): string
    {
        return '
        <div class="hcf_box">
        <style scoped>
        .hcf_box {
            display: flex;
            flex-direction: column;
        }

        .hcf_field {
            display: flex;
            /* justify-content: space-between; */
            margin-top: 8px;
        }

        .hcf_field select,
        .hcf_field input:not([type="checkbox"]),
        .hcf_field span {
            width: 100%;
            margin-left: 8px;
        }

        .hcf_field > label {
            width: 50%;
        }

        </style>
        ' . implode(array_map(function ($field) {
            return '<div class="meta-options hcf_field">' . $field->html() . '</div>';
        }, $this->fields)) . '</div>';
    }
}
