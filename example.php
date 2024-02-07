<?php require 'vendor/autoload.php';

use Inilim\TaskManager\Env;
use Inilim\TaskManager\Main;


// Прочие подключения
Env::init();
$main = new Main;
$main->setLogClass(new Logger);
