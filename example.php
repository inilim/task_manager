<?php require 'vendor/autoload.php';

use Inilim\TaskManager\Env;
use Inilim\TaskManager\IPDO;
use Inilim\TaskManager\Main;

// Прочие подключения
Env::init();

// var_dump(function_exists('integer'));
// exit();
// тут будет точка входа

$task  = IPDO::exec('SELECT * FROM tasks LIMIT 1', 1);

$main = new Main($task);
