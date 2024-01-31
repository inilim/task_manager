<?php

namespace Inilim\TaskManager;

use DateTime;

class Main
{
  protected $id;
  protected $created_at;
  protected $started_at;
  protected $class;
  protected $method;
  protected $completed_at;
  protected $params;

  function __construct(
    int $id,
    string $created_at,
    ?DateTime $started_at,
    string $class,
    string $method,
    ?DateTime $completed_at,
    ?string $params
  ) {
    $this->id = $id;
    $this->created_at = $created_at;
    $this->started_at = $started_at;
    $this->class = $class;
    $this->method = $method;
    $this->completed_at = $completed_at;
    $this->params = $params;
  }

  public function checkClass()
  {
    if (class_exists($this->class)) {
      echo ('класс существует <br>');
    } else echo ('ОШИБКА: класс не найден  <br>');
  }

  public function checkMethod()
  {
    if (method_exists($this->class, $this->method)) {
      echo ('метод существует <br>');
    } else echo ('ОШИБКА: метод не найден  <br>');
  }

  public function start()
  {
    echo ('Запуск задания <br>');
    echo ('Дата создания задания: ');
    echo ($this->created_at);
    echo ('<br>');
    echo ('Проверка контроллера: ');
    $this->checkClass();
    echo ('Проверка метода: ');
    $this->checkMethod();
  }

  protected function errorLog(): void
  {
  }
}
