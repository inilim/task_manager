<?php require 'vendor/autoload.php';

use Inilim\TaskManager\Env;
use Inilim\TaskManager\IPDO;
use Inilim\TaskManager\Main;
// var_dump(method_exists('Inilim\TaskManager\Main', 'checkTaskStatus'));
// exit();

// Прочие подключения
Env::init();

// var_dump(function_exists('integer'));
// exit();
// тут будет точка входа

$res  = IPDO::exec('SELECT * FROM tasks LIMIT 1', 1);

// $main = new Main($res->id, $res->created_at, $res->started_at, $res->class, $res->method, $res->complited_at, $res->params);
$main = new Main();
$main->checkClass('Inilim\TaskManager\Main');
$main->checkMethod('Inilim\TaskManager\Main', 'checkTaskStatus');

echo '<pre>';

var_dump(IPDO::status());

echo '<br>';

print_r($res);

echo '</pre>';
