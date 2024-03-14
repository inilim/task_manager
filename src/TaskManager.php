<?php

namespace Inilim\TaskManager;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Inilim\IPDO\IPDO;

class TaskManager
{
    protected array $task;

    public function __construct(
        protected readonly IPDO $db
    ) {
    }

    public function __invoke(): void
    {
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
        $manager_id = Uuid::uuid7()->toString();
        $started_at = (string)Carbon::now();

        $this->db->exec(
            'UPDATE tasks
            SET manager_id = :manager_id, started_at = :started_at
            WHERE started_at is NULL AND manager_id is NULL LIMIT 1',
            [
                'manager_id' => $manager_id,
                'started_at' => $started_at,
            ]
        );

        $this->task = $this->db->exec(
            'SELECT * FROM tasks WHERE manager_id = :manager_id AND complited_at is NULL AND started_at = :started_at',
            [
                'manager_id' => $manager_id,
                'started_at' => $started_at,
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
        $this->db->exec(
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
