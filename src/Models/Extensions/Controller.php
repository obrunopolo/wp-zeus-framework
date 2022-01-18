<?php

namespace Zeus\Models\Extensions;

interface Controller
{

    /**
     * Adds the controller hooks.
     * Is fired when the controller is initialized.
     *
     *
     * @return mixed
     */
    public function run();
}
