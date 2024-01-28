<?php

require __DIR__ . '/vendor/autoload.php';

use Inilim\TaskManager\Env;
use Inilim\TaskManager\IPDO;
use Inilim\TaskManager\Main;
// Прочие подключения
Env::init();

// var_dump(function_exists('integer'));
// exit();
// тут будет точка входа

$res  = IPDO::exec('SELECT * FROM test LIMIT 1', 1);

// $main = new Main($res);

echo '<pre>';

var_dump(IPDO::status());

echo '<br>';

print_r($res);

echo '</pre>';