-- Всего задач на выполение
SELECT COUNT(*) FROM tasks
WHERE
    (started_at is NULL AND manager_id is NULL)
OR
    (`repeat_after` is not null
    AND `complited_at` is not null
    AND (UNIX_TIMESTAMP(`complited_at`) + `repeat_after`) < UNIX_TIMESTAMP())

-- Задачи которые стартанули но не завершились
SELECT * FROM tasks
WHERE
    started_at is not null
    AND complited_at is null