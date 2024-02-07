<?php require 'vendor/autoload.php';

use Inilim\TaskManager\Env;
use Inilim\TaskManager\TaskManager;


// Прочие подключения
Env::init();
$main = new TaskManager;
