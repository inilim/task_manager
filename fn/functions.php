<?php

use Inilim\Array\Array_;
use Inilim\Integer\Integer;
use Inilim\JSON\JSON;
use Inilim\TaskManager\Env;

function integer(): Integer
{
    return Env::$components['integer'];
}

function array_(): Array_
{
    return Env::$components['array'];
}

function json(): JSON
{
    return Env::$components['json'];
}
