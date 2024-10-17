<?php

declare(strict_types=1);

namespace Inilim\TaskManager;

use Carbon\Carbon;
use Inilim\IPDO\IPDO;
use Inilim\IPDO\Exception\FailedExecuteException;

/**
 * @psalm-type Task = array{id:int,
 * manager_id:?string,
 * started_at:?string,
 * execute_after:?string,
 * complited_at:?string,
 * class:string,
 * method:string,
 * created_at:string,
 * repeat_after:?int,
 * counter:int,
 * updated_at:string,
 * params:?string}
 */
final class TaskManager
{
    /**
     * @var ?Task
     */
    protected ?array $task      = null;
    protected ?\Closure $logger = null;
    protected IPDO $db;

    function __construct(
        IPDO $db,
        ?\Closure $logger = null
    ) {
        if ($logger !== null) {
            $this->logger = $logger;
        }
        $this->db = $db;
    }

    /**
     * @deprecated use one()
     */
    function __invoke(): void
    {
        $this->one();
    }

    function one(): void
    {
        try {
            $this->process();
        } catch (\Throwable $e) {
            $this->errorLog([], $e);
        }
    }

    function many(int $seconds): void
    {
        while (true) {
            $start = \time();
            try {
                if ($this->initTask() && $this->checkTask()) {
                    $this->startTask();
                    $this->complitedTask();
                } else {
                    \sleep(1);
                }
            } catch (\Throwable $e) {
                $this->errorLog([], $e);
                $e = null;
            }

            $seconds -= \time() - $start;
            if ($seconds <= 0) {
                break;
            }
        }
    }

    function setLogger(?\Closure $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    // ------------------------------------------------------------------
    // 
    // ------------------------------------------------------------------

    protected function process(): void
    {
        if (!$this->initTask() || !$this->checkTask()) return;
        $this->startTask();
        $this->complitedTask();
    }

    protected function checkTask(): bool
    {
        if (!$this->checkSignature()) {
            $this->errorLog(['Неизвестная сигнатура задачи'], null, $this->task);
            $this->complitedTask();
            return false;
        }

        if (!$this->checkClass()) {
            $this->errorLog(['Класс не существует'], null, $this->task);
            $this->complitedTask();
            return false;
        }

        if (!$this->checkMethod()) {
            $this->errorLog(['Метод класса не существует'], null, $this->task);
            $this->complitedTask();
            return false;
        }

        return true;
    }

    protected function initTask(): bool
    {
        $manager_id = \_uuid()->v7();
        $started_at = (string)Carbon::now();

        // Помечаем свободную задача manager_id
        try {
            $this->db->exec(
                'UPDATE `tasks`
                SET `manager_id` = :manager_id,
                    `started_at` = :started_at,
                    `counter` = (`counter` + 1),
                    -- INFO если скрипт крашится, то complited_at не обновляется, и из-за этого задачи типа "repeat_after" стопорятся
                    `complited_at` = IF(`complited_at` is null, null, :started_at)
                WHERE
                    (`manager_id` is NULL
                    AND `started_at` is NULL
                    AND `execute_after` is NULL)
                OR
                    (`manager_id` is NULL
                    AND `started_at` is NULL
                    AND `execute_after` is not NULL
                    AND CURRENT_TIMESTAMP() >= `execute_after`)
                OR
                    (`repeat_after` is not NULL
                    AND `started_at` is not NULL
                    AND `complited_at` is not NULL
                    AND `started_at` <= `complited_at`
                    AND (UNIX_TIMESTAMP(`complited_at`) + `repeat_after`) < UNIX_TIMESTAMP())
                    
                ORDER BY `updated_at` ASC
                LIMIT 1',
                [
                    'manager_id' => $manager_id,
                    'started_at' => $started_at,
                ]
            );
        } catch (FailedExecuteException $e) {
            $this->errorLog($e->getError(), $e);
            return false;
        }

        // забираем помеченную задачу
        try {
            // @phpstan-ignore-next-line
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
            $this->errorLog($e->getError(), $e);
            return false;
        }

        if (!$this->task) return false;
        return true;
    }

    protected function checkClass(): bool
    {
        // @phpstan-ignore-next-line
        return \class_exists($this->task['class']);
    }

    protected function checkMethod(): bool
    {
        // @phpstan-ignore-next-line
        return \method_exists($this->task['class'], $this->task['method']);
    }

    protected function checkSignature(): bool
    {
        if (!\is_string($this->task['class'])) {
            return false;
        }
        if (!\is_string($this->task['method'])) {
            return false;
        }
        if (!\is_string($this->task['manager_id'])) {
            return false;
        }
        if (!\array_key_exists('params', $this->task)) {
            return false;
        }
        return true;
    }

    protected function startTask(): void
    {
        $class  = \strval($this->task['class'] ?? '');
        $method = \strval($this->task['method'] ?? '');
        try {
            $object = new $class;
            $object->$method($this->task['params'], $this->task);
        } catch (\Throwable $e) {
            $this->errorLog([], $e, $this->task);
        }
    }

    protected function complitedTask(): void
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
            $this->errorLog($e->getError(), $e, $this->task);
        }
    }

    /**
     * @param mixed[] $messages
     * @param mixed[]|null $task
     */
    protected function errorLog(
        array $messages = [],
        ?\Throwable $e  = null,
        ?array $task    = null
    ): void {
        if ($this->logger === null) return;
        try {
            $this->logger->__invoke($messages, $e, $task);
        } catch (\Throwable $e) {
        }
    }
}
