<?php require 'vendor/autoload.php';

use Inilim\TaskManager\Env;
use Inilim\TaskManager\IPDO;
use Inilim\TaskManager\Main;
use Carbon\Carbon;
use Ramsey\Uuid\Rfc4122\UuidV7;

// Прочие подключения
Env::init();

// тут будет точка входа
$task_manager_id = UuidV7::uuid7(); // 76254234
$started_at = (string)Carbon::now();

$task  = IPDO::exec(
    'UPDATE tasks
    SET task_manager_id = :task_manager_id, started_at = :started_at
    WHERE started_at is NULL AND manager_id is NULL LIMIT 1',
    [
        'task_manager_id' => $task_manager_id,
        'started_at'      => $started_at,
    ]
);

$task  = IPDO::exec('SELECT * FROM tasks WHERE task_manager_id = :task_manager_id AND complited_at is NULL AND started_at = :started_at', [
    'task_manager_id' => $task_manager_id,
    'started_at'      => $started_at,
], 2);

if (sizeof($task) > 1) {
    // TODO ЭПИК!
}

new Main($task);
