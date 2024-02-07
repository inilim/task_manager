<?php

$a = [];
$a = null;
$a = 0;
$a = false;



if (is_null($a)) {
}



if ($a === null) {
}

























exit();
try {
  $mysql = new mysqli('localhost', 'root', 'root', 'task_manager'); // подключаемся к базе 
} catch (Exception $e) {
  echo 'Ошибка при подключении к базе данных: ' . $e->getMessage();
  exit();
}


$tasks = $mysql->query("SELECT * FROM test LIMIT 1"); // находим первую строку
$task = $tasks->fetch_assoc(); // парсер в массив

if ($task) {
  print_r($task); // выполнение задания
  // $id = $task["id"];
  echo '<br>';
  $mysql->query("DELETE FROM test WHERE id = '$task[id]'"); // удаление задания
} else
  echo 'Нет заданий <br>';


// header("Refresh:0");

// echo '<script>';
// echo 'window.location.reload();';
// echo '</script>';
$mysql->close();



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