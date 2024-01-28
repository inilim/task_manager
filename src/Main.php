<?php

namespace Inilim\TaskManager;

class Main
{
  private int $id;
  private int $start;
  private array $status; //planned, starting, done, error
  private bool $repeat;

  /**
   * @param int $id;
   * @param int $start;
   * @param int[] $status;
   * @param bool/null $repeat;
   */

  function __construct(
    int $id,
    int $start,
    array $status,
    ?bool $repeat,
  ) {
    $this->id = $id;
    $this->start = $start;
    $this->status = $status;
    $this->repeat = $repeat;
  }

  function checkTaskStatus()
  {
    if ($this->status) {
    } else return;
  }


  function checkStatusWork()
  {
  }
}
