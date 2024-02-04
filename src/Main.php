<?php

namespace Inilim\TaskManager;

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
  }

  public function checkClass()
  {
    if (class_exists($this->task['class'])) {
      echo 'класс существует <br>';
    } else echo 'ОШИБКА: класс не найден  <br>';
  }

  public function checkMethod()
  {
    if (method_exists($this->task['class'], $this->task['method'])) {
      echo 'метод существует <br>';
    } else echo 'ОШИБКА: метод не найден  <br>';
  }

  public function start()
  {
  }

  protected function errorLog(): void
  {
  }
}
