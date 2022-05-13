<?php

namespace Zeus\Framework\Contracts;

abstract class Component
{

    /** @var array */
    public $props;

    function __construct(array $props = array())
    {
        $this->props = $props;
    }

    abstract function html(): string;

    function render()
    {
        echo $this->html();
    }
}
