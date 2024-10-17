<?php

require 'vendor/autoload.php';

use Inilim\IPDO\IPDOMySQL;
use Inilim\TaskManager\TaskManager;


$db = new IPDOMySQL(
    '',
    '',
    '',
);

$manager = new TaskManager($db);

$manager->setLogger(function (array $messages, ?\Throwable $e, ?array $task) {
    // 
});

$manager();
