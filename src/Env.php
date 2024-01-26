<?php

namespace Inilim\TaskManager;

use Inilim\Array\Array_;
use Inilim\Integer\Integer;
use Inilim\JSON\JSON;
use Inilim\TaskManager\IPDO;

class Env
{
    public static array $components = [
        'json'    => null,
        'array'   => null,
        'integer' => null,
    ];

    public static function init(): void
    {
        IPDO::$login = 'root';
        IPDO::$pass  = '';
        IPDO::$name  = 'noks_local'; // имя базы

        self::$components['json']    = new JSON;
        self::$components['integer'] = new Integer;
        self::$components['array']   = new Array_;
    }
}
