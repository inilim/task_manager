CREATE TABLE `tasks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `manager_id` varchar(36) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `execute_after` timestamp NULL DEFAULT NULL,
  `complited_at` timestamp NULL DEFAULT NULL,
  `class` varchar(255) NOT NULL,
  `method` varchar(255)  NOT NULL,
  `created_at` timestamp NOT NULL,
  `repeat_after` int unsigned DEFAULT NULL COMMENT 'seconds',
  `counter` int unsigned NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `params` text,
  PRIMARY KEY (`id`)
)