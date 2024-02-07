<?php

namespace Inilim\TaskManager;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class TaskManager
{
    protected array $task;

    public function __construct(
        protected IPDO $db
    ) {
        if (!$this->initTask()) return;

        if (!$this->checkClass()) {
            $this->errorLog(['Класс не существует']);
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

    protected function initTask(): bool
    {
        $task_manager_id = Uuid::uuid7()->toString();
        $started_at      = (string)Carbon::now();

        IPDO::exec(
            'UPDATE tasks
            SET task_manager_id = :task_manager_id, started_at = :started_at
            WHERE started_at is NULL AND manager_id is NULL LIMIT 1',
            [
                'task_manager_id' => $task_manager_id,
                'started_at'      => $started_at,
            ]
        );

        $this->task = IPDO::exec(
            'SELECT * FROM tasks WHERE task_manager_id = :task_manager_id AND complited_at is NULL AND started_at = :started_at',
            [
                'task_manager_id' => $task_manager_id,
                'started_at'      => $started_at,
            ],
            1
        );

        if (!$this->task) return false;
        return true;
    }

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
    }
}
