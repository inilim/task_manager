<?php

namespace Inilim\TaskManager;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Inilim\IPDO\IPDO;
use Inilim\IPDO\Exception\FailedExecuteException;

class TaskManager
{
    /**
     * @var mixed[]|array{}|null
     */
    protected ?array $task = null;
    protected ?\Closure $logger = null;

    public function __construct(
        protected readonly IPDO $db,
        ?\Closure $logger = null,
    ) {
        if ($logger !== null) {
            $this->logger = $logger;
        }
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

        // Помечаем свободную задача manager_id
        try {
            $this->db->exec(
                'UPDATE `tasks`
                SET `manager_id` = :manager_id,
                    `started_at` = :started_at,
                    `counter` = (`counter` + 1)
                WHERE
                    (`manager_id` is NULL AND `started_at` is NULL)
                OR
                    (`started_at` is not NULL
                    AND `repeat_after` is not NULL
                    AND `complited_at` is not NULL
                    AND `started_at` <= `complited_at`
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

        // забираем помеченную задачу
        try {
            $this->task = $this->db->exec(
                'SELECT * FROM `tasks`
                WHERE `manager_id` = :manager_id
                AND `started_at` = :started_at',
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
            $object->$method($this->task['params'], $this->task);
        } catch (\Throwable $e) {
            $this->errorLog(e: $e, task: $this->task);
        }
    }

    protected function endTask(): void
    {
        try {
            // `manager_id` = IF(`repeat_after` is NULL, `manager_id`, NULL)
            $this->db->exec(
                'UPDATE `tasks`
                SET `complited_at` = :complited_at
                WHERE `manager_id` = :manager_id',
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
     * @param string[] $messages
     * @param mixed[]|null $task
     */
    protected function errorLog(array $messages = [], ?\Throwable $e = null, ?array $task = null): void
    {
        if ($this->logger === null) return;
        try {
            ($this->logger)($messages, $e, $task);
        } catch (\Throwable) {
        }
    }
}
