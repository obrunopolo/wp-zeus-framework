<?php

use Zeus\App;

function zeus()
{
    return App::getInstance();
}

function is_dev()
{
    return defined('ZEUS_DEV') && ZEUS_DEV === true;
}
