CREATE TABLE `tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `manager_id` varchar(36) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `complited_at` timestamp NULL DEFAULT NULL,
  `class` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL,
  `repeat_after` int DEFAULT NULL,
  `counter` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `params` text,
  PRIMARY KEY (`id`)
)