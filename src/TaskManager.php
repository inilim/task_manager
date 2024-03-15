<?php

namespace Inilim\TaskManager;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Inilim\IPDO\IPDO;
use Inilim\IPDO\Exception\FailedExecuteException;

class TaskManager
{
    protected ?array $task = null;
    protected ?\Closure $logger = null;

    public function __construct(
        protected readonly IPDO $db
    ) {
    }

    public function __invoke(): void
    {
        if (!$this->initTask()) return;

        if (!$this->checkClass()) {
            $this->errorLog(messages: ['Класс не существует'], task: $this->task);
            $this->endTask();
            return;
        }

        if (!$this->checkMethod()) {
            $this->errorLog(messages: ['Метод класса не существует'], task: $this->task);
            $this->endTask();
            return;
        }

        $this->start();
        $this->endTask();
    }

    public function setLogger(\Closure $logger): void
    {
        $this->logger = $logger;
    }

    // ------------------------------------------------------------------
    // protected
    // ------------------------------------------------------------------

    protected function initTask(): bool
    {
        $manager_id = Uuid::uuid7()->toString();
        $started_at = (string)Carbon::now();

        try {
            $this->db->exec(
                'UPDATE tasks
            SET `manager_id` = :manager_id, `started_at` = :started_at, `counter` = (`counter` + 1)
            WHERE
                (started_at is NULL AND manager_id is NULL)
            OR
                (`repeat_after` is not null
                AND `complited_at` is not null
                AND (UNIX_TIMESTAMP(`complited_at`) + `repeat_after`) < UNIX_TIMESTAMP())
            LIMIT 1',
                [
                    'manager_id' => $manager_id,
                    'started_at' => $started_at,
                ]
            );
        } catch (FailedExecuteException $e) {
            $this->errorLog(messages: $e->getErrors(), e: $e);
            return false;
        }

        try {
            $this->task = $this->db->exec(
                'SELECT * FROM tasks WHERE manager_id = :manager_id AND started_at = :started_at',
                [
                    'manager_id' => $manager_id,
                    'started_at' => $started_at,
                ],
                1
            );
        } catch (FailedExecuteException $e) {
            $this->errorLog(messages: $e->getErrors(), e: $e);
            return false;
        }

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
            $this->errorLog(e: $e, task: $this->task);
        }
    }

    protected function endTask(): void
    {
        try {
            $this->db->exec(
                'UPDATE tasks SET complited_at = :complited_at WHERE manager_id = :manager_id',
                [
                    'complited_at' => (string)Carbon::now(),
                    'manager_id'   => $this->task['manager_id'],
                ]
            );
        } catch (FailedExecuteException $e) {
            $this->errorLog(messages: $e->getErrors(), e: $e, task: $this->task);
        }
    }

    /**
     * Undocumented function
     *
     * @param array $messages
     * @param null|\Throwable|null $e
     * @param null|array|null $task
     * @return void
     */
    protected function errorLog(array $messages = [], null|\Throwable $e = null, null|array $task = null): void
    {
        if ($this->logger === null) return;
        try {
            ($this->logger)($messages, $e, $task);
        } catch (\Throwable) {
        }
    }
}
