-- JACLUPAN Basketball League - Database Schema
-- Copy and run in your MySQL (phpMyAdmin, MySQL Shell, etc.)

-- Optional: create database (uncomment and edit name if needed)
-- CREATE DATABASE IF NOT EXISTS `jaclupan_basketball` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `jaclupan_basketball`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables (order matters due to FKs)
DROP TABLE IF EXISTS `players`;
DROP TABLE IF EXISTS `teams`;

-- Teams table
CREATE TABLE `teams` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `team_name` VARCHAR(255) NOT NULL,
  `coach_name` VARCHAR(255) NOT NULL,
  `contact_number` VARCHAR(20) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `division` VARCHAR(50) NOT NULL,
  `players` TEXT NOT NULL,
  `logo_path` VARCHAR(255) DEFAULT NULL,
  `players_registered` TINYINT(1) NOT NULL DEFAULT 0,
  `registration_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `username` VARCHAR(255) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_team_email` (`email`),
  UNIQUE KEY `unique_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Players table
CREATE TABLE `players` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `birthdate` DATE NOT NULL,
  `jersey_number` INT NOT NULL,
  `position` VARCHAR(50) DEFAULT NULL,
  `photo_path` VARCHAR(255) NOT NULL,
  `document_path` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_players_team_id` (`team_id`),
  CONSTRAINT `fk_players_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_team_jersey` (`team_id`, `jersey_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Optional seed: none required. Create teams via the app registration form.

