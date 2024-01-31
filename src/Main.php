<?php

namespace Inilim\TaskManager;

use DateTime;

class Main
{
  // protected $id;
  // protected $created_at;
  // protected $started_at;

  // function __construct(
  //   int $id,
  //   DateTime $created_at,
  //   ?DateTime $started_at,
  //   string $class,
  //   string $method,
  //   ?DateTime $completed_at,
  //   ?string $params
  // ) {
  //   $this->id = $id;
  //   $this->created_at = $created_at;
  //   $this->created_at = $started_at;
  // }

  public function checkClass($class)
  {
    if (class_exists($class)) {
      echo ('Класс существует <br>');
    } else echo('Класс не найден  <br>');
  }


  public function checkMethod($class, $method)
  {
    if(method_exists($class, $method)) {
      echo ('Метод существует <br>');
    } else echo('Метод не найден  <br>');
  }

  protected function errorLog(): void
  {
  }

  function start() {
    
  }
}
