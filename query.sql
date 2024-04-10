-- Всего задач на выполение
SELECT COUNT(*) FROM tasks
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

-- Задачи которые стартанули но не завершились
SELECT * FROM tasks
WHERE
    started_at is not null
    AND complited_at is null