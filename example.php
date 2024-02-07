<?php require 'vendor/autoload.php';

use Inilim\TaskManager\Env;
use Inilim\TaskManager\IPDO;
use Inilim\TaskManager\Main;

use Carbon\Carbon;

// var_dump($argv);
exit();
// Прочие подключения
Env::init();

// тут будет точка входа

$startet_at = Carbon::now();
$task_id = rand();

$task  = IPDO::exec('
UPDATE tasks
SET task_mager_id = ' . $task_manager_id . ' 
SET started_at = ' . $startet_at . ' 
WHERE started_at is NULL AND manager_id is NULL LIMIT 1', 1);
$task  = IPDO::exec('SELECT * FROM tasks WHERE started_at = $startet_at LIMIT 1', 1);
// $task  = IPDO::exec('SELECT * FROM tasks WHERE started_at is NULL LIMIT 1', 1);
// $manager_id = new Main($task);


// UPDATE tasks
// SET manager_id = :uniq_id
// WHERE started_at is NULL AND manager_id is NULL LIMIT 1