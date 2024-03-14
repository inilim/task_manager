<?php

require 'vendor/autoload.php';

use Inilim\TaskManager\TaskManager;
use Inilim\IPDO\IPDOMySQL;
use Inilim\Integer\Integer;
use Inilim\Array\Array_;


$db = new IPDOMySQL(
    '',
    '',
    '',
    new Integer,
    new Array_
);

new TaskManager($db);
