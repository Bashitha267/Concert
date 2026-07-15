-- INZANITY HipHop Concert Database Schema
-- You can import this file directly into phpMyAdmin or run it via MySQL command line.

CREATE DATABASE IF NOT EXISTS `concert_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `concert_db`;

-- 1. PACKAGES TABLE
DROP TABLE IF EXISTS `packages`;
CREATE TABLE `packages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(2) NOT NULL UNIQUE, -- 'VP', 'VV', 'GN'
  `name` VARCHAR(50) NOT NULL,       -- 'VIP', 'VVIP', 'General'
  `price` DECIMAL(10, 2) NOT NULL,
  `total_seats` INT NOT NULL,
  `available_seats` INT NOT NULL,
  `description` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Populate default packages
INSERT INTO `packages` (`code`, `name`, `price`, `total_seats`, `available_seats`, `description`) VALUES
('VP', 'VIP Package', 3500.00, 50, 50, 'Exclusive front rows access, official event lanyard, and 1 free beverage.'),
('VV', 'VVIP Package', 5000.00, 30, 30, 'Backstage pass, meet & greet session, premium front lounge seating, and exclusive merch bundle.'),
('GN', 'General Package', 1500.00, 200, 200, 'Standard entry to the main arena with dynamic sound and visuals experience.')
ON DUPLICATE KEY UPDATE `price`=VALUES(`price`), `total_seats`=VALUES(`total_seats`), `available_seats`=VALUES(`available_seats`);

-- 2. BOOKINGS TABLE
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_ref` VARCHAR(20) NOT NULL UNIQUE, -- Auto-generated like VP1000, VV1001, GN1002
  `name` VARCHAR(150) NOT NULL,
  `nic` VARCHAR(20) NOT NULL,
  `whatsapp` VARCHAR(20) NOT NULL,
  `package_code` VARCHAR(2) NOT NULL,
  `seats` INT NOT NULL,
  `receipt_path` VARCHAR(255) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `attended_seats` INT NOT NULL DEFAULT 0, -- To track how many of their seats have entered the event
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`package_code`) REFERENCES `packages` (`code`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. ATTENDANCE LOG TABLE
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_ref` VARCHAR(20) NOT NULL,
  `seats_confirmed` INT NOT NULL,
  `scanned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_ref`) REFERENCES `bookings` (`booking_ref`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
