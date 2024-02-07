<?php

namespace Inilim\TaskManager;

use Carbon\Carbon;

class Main
{
  protected $task;

  function __construct(
    array $task
  ) {
    $this->task = $task;
    echo 'Проверка задания <br>';
    echo 'Дата создания задания: ';
    echo $this->task['created_at'];
    echo '<br>';
    echo 'Проверка контроллера: ';
    $this->checkClass();
    echo 'Проверка метода: ';
    $this->checkMethod();
    $this->start();
    $this->endTask();
  }

  protected function checkClass()
  {
    if (class_exists($this->task['class'])) {
      echo 'класс существует <br>';
    } else {
      echo 'ОШИБКА: класс не найден  <br>';
      $this->endTask();
    }
  }

  protected function checkMethod()
  {
    if (method_exists($this->task['class'], $this->task['method'])) {
      echo 'метод существует <br>';
    } else {
      echo 'ОШИБКА: метод не найден  <br>';
      $this->endTask();
    }
  }

  public function start()
  {
    $class = $this->task['class'];
    $method = $this->task['method'];
    try {
      $object = new $class;
      $object->$method($this->task['params']);
    } catch (\Throwable $e) {
      echo 'Ошибка: ' . $e->getMessage();
      $this->errorLog($e);
    }
  }


  protected function endTask()
  {
    IPDO::exec("
  UPDATE tasks
  SET complited_at = ' . Carbon::now() . ' 
  WHERE task_mager_id = ' . $this->task['task_manager_id'] . '", 1);
  }

  protected function errorLog($e): void
  {
  }
}
