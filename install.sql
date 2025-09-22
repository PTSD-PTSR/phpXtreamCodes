DROP TABLE IF EXISTS `videos`;



CREATE TABLE `videos` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `path` text COLLATE utf8mb4_unicode_ci,
                        `size` bigint(20) DEFAULT NULL,
                        `extension` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `folder` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `is_series` tinyint(1) DEFAULT '0',
                        `meta_found` tinyint(1) DEFAULT '0',
                        `description` mediumtext COLLATE utf8mb4_unicode_ci,
                        `cover_url` mediumtext COLLATE utf8mb4_unicode_ci,
                        `cover_file` text COLLATE utf8mb4_unicode_ci,
                        `source_url` text COLLATE utf8mb4_unicode_ci,
                        `year` text COLLATE utf8mb4_unicode_ci,
                        `actors` mediumtext COLLATE utf8mb4_unicode_ci,
                        `country` mediumtext COLLATE utf8mb4_unicode_ci,
                        `genre` mediumtext COLLATE utf8mb4_unicode_ci,
                        `director` mediumtext COLLATE utf8mb4_unicode_ci,
                        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `videos`
  ADD `codec_video` MEDIUMTEXT NULL DEFAULT NULL AFTER `director`,
  ADD `codec_audio` MEDIUMTEXT NULL DEFAULT NULL AFTER `codec_video`;

ALTER TABLE `videos`
  ADD `movie_image` MEDIUMTEXT NULL DEFAULT NULL AFTER `cover_file`;
