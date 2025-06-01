CREATE DATABASE IF NOT EXISTS `ece_hub`;
USE `ece_hub`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Table: users
CREATE TABLE `users` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `username` varchar(50) NOT NULL,
                         `email` varchar(100) NOT NULL,
                         `password` varchar(255) NOT NULL,
                         `profile_picture` varchar(255) DEFAULT NULL,
                         `overlay` varchar(255) DEFAULT NULL,
                         `description` text DEFAULT NULL,
                         `is_admin` tinyint(1) DEFAULT 0,
                         `cv_xml` varchar(255) DEFAULT NULL,
                         `partenaire_ece` tinyint(4) DEFAULT 0,
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `username` (`username`),
                         UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Insert admin with new password hash
INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_picture`, `overlay`, `description`, `is_admin`, `cv_xml`, `partenaire_ece`) VALUES
    (1, 'Admin', 'admin@ece.fr', '$2y$10$uVlHf7xYzIz4r9j3m6HgUOXYXScFlFjiFyYr2gxopLfIqfZDsIfdG', NULL, NULL, NULL, 1, NULL, 0);

-- Table: posts
CREATE TABLE `posts` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `username` varchar(255) NOT NULL,
                         `content` text NOT NULL,
                         `location` varchar(255) DEFAULT NULL,
                         `datetime` datetime DEFAULT NULL,
                         `feeling` varchar(255) DEFAULT NULL,
                         `media_path` varchar(255) DEFAULT NULL,
                         `created_at` timestamp NULL DEFAULT current_timestamp(),
                         `likes` int(11) DEFAULT 0,
                         `privacy` enum('public','friends','private') NOT NULL DEFAULT 'public',
                         `visible_to` text DEFAULT NULL,
                         `visibility` enum('public','all_friends','selected_friends','private') NOT NULL,
                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: comments
CREATE TABLE `comments` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `post_id` int(11) NOT NULL,
                            `username` varchar(255) NOT NULL,
                            `comment` text NOT NULL,
                            `created_at` timestamp NULL DEFAULT current_timestamp(),
                            PRIMARY KEY (`id`),
                            KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: friends
CREATE TABLE `friends` (
                           `id` int(11) NOT NULL AUTO_INCREMENT,
                           `user1` varchar(255) NOT NULL,
                           `user2` varchar(255) NOT NULL,
                           `created_at` timestamp NULL DEFAULT current_timestamp(),
                           PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: messages
CREATE TABLE `messages` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `sender` varchar(255) NOT NULL,
                            `recipient` varchar(255) NOT NULL,
                            `message` text DEFAULT NULL,
                            `timestamp` timestamp NULL DEFAULT current_timestamp(),
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: notifications
CREATE TABLE `notifications` (
                                 `id` int(11) NOT NULL AUTO_INCREMENT,
                                 `receiver` varchar(255) NOT NULL,
                                 `sender` varchar(255) NOT NULL,
                                 `types` varchar(255) NOT NULL,
                                 `statut` varchar(255) NOT NULL DEFAULT 'unread',
                                 `created_at` timestamp NULL DEFAULT current_timestamp(),
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: chats
CREATE TABLE `chats` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `user1_id` int(11) NOT NULL,
                         `user2_id` int(11) NOT NULL,
                         PRIMARY KEY (`id`),
                         KEY `user1_id` (`user1_id`),
                         KEY `user2_id` (`user2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: group_chats
CREATE TABLE `group_chats` (
                               `id` int(11) NOT NULL AUTO_INCREMENT,
                               `group_name` varchar(255) NOT NULL,
                               `created_by` varchar(255) NOT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: group_members
CREATE TABLE `group_members` (
                                 `id` int(11) NOT NULL AUTO_INCREMENT,
                                 `group_id` int(11) NOT NULL,
                                 `username` varchar(255) NOT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: group_messages
CREATE TABLE `group_messages` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `group_id` int(11) NOT NULL,
                                  `sender` varchar(255) NOT NULL,
                                  `message` text NOT NULL,
                                  `timestamp` timestamp NULL DEFAULT current_timestamp(),
                                  PRIMARY KEY (`id`),
                                  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: job_offers
CREATE TABLE `job_offers` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `sender_username` varchar(255) NOT NULL,
                              `receiver_username` varchar(255) NOT NULL,
                              `post_id` int(11) NOT NULL,
                              `offer_type` enum('stage','apprentissage','temporaire','permanent') NOT NULL,
                              `created_at` timestamp NULL DEFAULT current_timestamp(),
                              PRIMARY KEY (`id`),
                              KEY `sender_username` (`sender_username`(250)),
                              KEY `receiver_username` (`receiver_username`(250)),
                              KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: post_likes
CREATE TABLE `post_likes` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `user_id` int(11) DEFAULT NULL,
                              `post_id` int(11) DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              UNIQUE KEY `user_id` (`user_id`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table: post_visibility
CREATE TABLE `post_visibility` (
                                   `id` int(11) NOT NULL AUTO_INCREMENT,
                                   `post_id` int(11) NOT NULL,
                                   `friend_username` varchar(255) NOT NULL,
                                   PRIMARY KEY (`id`),
                                   KEY `post_id` (`post_id`),
                                   KEY `friend_username` (`friend_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

COMMIT;
