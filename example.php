<?php require 'vendor/autoload.php';

use Inilim\TaskManager\Env;
use Inilim\TaskManager\IPDO;
use Inilim\TaskManager\Main;

// Прочие подключения
Env::init();

// var_dump(function_exists('integer'));
// тут будет точка входа

$task  = IPDO::exec('SELECT * FROM test LIMIT 1', 1);

if ($task)
  var_dump($task);
else
  echo 'Нет заданий';

// echo 'Cоздание таблицы <br>';

// IPDO::exec('CREATE TABLE `test` (
//   id INT(11) NOT NULL AUTO_INCREMENT,
//   name VARCHAR(255) NOT NULL,
//   PRIMARY KEY(id)
// )');

// $sql = "SHOW TABLES LIKE 'test'";
// var_dump(IPDO::exec($sql));

// echo 'Удаление таблицы test <br>';

// IPDO::exec('DROP TABLE `test`');

// $main = new Main($task);

use Carbon\Carbon;

// $date = Carbon::now()->DateTimeZone('Europe/Moscow');

// echo $date->DateTimeZone();            // fr_FR
// echo date('m-l H:i:s', time() + 10800). '<br>';
// echo date('m-d-Y H:i:s', strtotime("+ 3 Hour")). '<br>';
// echo "\n";
// printf("Now: %s", Carbon::now());
// phpinfo();
// echo $_SERVER['HTTP_USER_AGENT'];

// $mysql->query('INSERT INTO `tasks` (`id`, `created_at`, `started_at`, `class`, `method`, `completed_at`, `params`) VALUES (NULL, '2024-02-04 09:06:26', NULL, 'Inilim\\TaskManager\\IPDO\'', 'checkStatusWork', NULL, NULL);

exit();
