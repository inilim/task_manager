<?php

namespace Inilim\TaskManager;

use Carbon\Carbon;

class Main
{
  public function __construct(
    protected array $task
  ) {
    if (!$this->checkClass()) {
      $this->errorLog(['Класса не существует']);
      $this->endTask();
      return;
    }

    if (!$this->checkMethod()) {
      $this->errorLog(['Метод класса не существует']);
      $this->endTask();
      return;
    }

    $this->start();
    $this->endTask();
  }

  // ------------------------------------------------------------------
  // protected
  // ------------------------------------------------------------------

  protected function checkClass(): bool
  {
    return \class_exists($this->task['class']);
  }

  protected function checkMethod(): bool
  {
    return \method_exists($this->task['class'], $this->task['method']);
  }

  protected function start(): void
  {
    $class  = $this->task['class'];
    $method = $this->task['method'];
    try {
      $object = new $class;
      $object->$method($this->task['params']);
    } catch (\Throwable $e) {
      $this->errorLog($e);
    }
  }

  protected function endTask(): void
  {
    IPDO::exec(
      'UPDATE tasks SET complited_at = :complited_at WHERE manager_id = :manager_id',
      [
        'complited_at' => (string)Carbon::now(),
        'manager_id'   => $this->task['manager_id'],
      ]
    );
  }

  protected function errorLog($e): void
  {
    // TODO ТАЙНА
  }
}
