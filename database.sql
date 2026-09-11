-- ==========================================================
-- AGRI STORAGE: Smart Cold Storage Finder & Booking System
-- Database Setup Script for MySQL / MariaDB (phpMyAdmin / Cloud ready)
-- Hackathon: KALPVRUKSH 2.0 Mini Hackathon - Silver Oak University
-- Problem Statement: P21 – AgriTech EASY
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `agristorage` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agristorage`;

-- Drop tables if exists to allow clean re-import
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `cold_storages`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------
-- 1. Table structure for table `users`
-- ----------------------------------------------------------
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `role` ENUM('farmer', 'owner') NOT NULL DEFAULT 'farmer',
  `location` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_email` (`email`),
  INDEX `idx_user_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Table structure for table `cold_storages`
-- ----------------------------------------------------------
CREATE TABLE `cold_storages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `owner_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `available_capacity` DECIMAL(10,2) NOT NULL,
  `total_capacity` DECIMAL(10,2) NOT NULL,
  `temperature` VARCHAR(50) NOT NULL,
  `price_per_kg` DECIMAL(10,2) NOT NULL,
  `supported_crops` TEXT NOT NULL,
  `minimum_quantity` DECIMAL(10,2) NOT NULL DEFAULT 50.00,
  `contact` VARCHAR(20) NOT NULL,
  `status` ENUM('Available', 'Full', 'Inactive') NOT NULL DEFAULT 'Available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_storage_owner` (`owner_id`),
  INDEX `idx_storage_status` (`status`),
  INDEX `idx_storage_location` (`location`),
  CONSTRAINT `fk_storage_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Table structure for table `bookings`
-- ----------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `farmer_id` INT NOT NULL,
  `storage_id` INT NOT NULL,
  `pickup_location` VARCHAR(150) NOT NULL DEFAULT 'Ahmedabad',
  `crop` VARCHAR(100) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_cost` DECIMAL(12,2) NOT NULL,
  `has_insurance` TINYINT(1) NOT NULL DEFAULT 0,
  `insurance_rate` DECIMAL(5,2) NOT NULL DEFAULT 85.00,
  `insurance_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `damage_status` ENUM('None', 'Reported', 'Compensated') NOT NULL DEFAULT 'None',
  `damage_cause` VARCHAR(255) NULL,
  `affected_crop` VARCHAR(100) NULL,
  `affected_quantity` DECIMAL(10,2) NULL,
  `compensation_amount` DECIMAL(10,2) NULL,
  `status` ENUM('Pending', 'Accepted', 'Rejected', 'Completed') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_booking_farmer` (`farmer_id`),
  INDEX `idx_booking_storage` (`storage_id`),
  INDEX `idx_booking_status` (`status`),
  CONSTRAINT `fk_booking_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_storage` FOREIGN KEY (`storage_id`) REFERENCES `cold_storages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Demo Data Inserts
-- Default passwords:
-- Farmer: demo@coldconnect.test / demo123
-- Owner:  owner@coldconnect.test / owner123
-- ----------------------------------------------------------

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `location`, `created_at`) VALUES
(1, 'Ramesh Patel (Demo Farmer)', 'demo@coldconnect.test', '$2y$10$ZNESvTtsEi4o3zxHOjBx2OsVOY4QUHq2OisOv6yKB7jP.nvZbmIsK', '+91 98765 43210', 'farmer', 'Ahmedabad', NOW()),
(2, 'Haresh Shah (Demo Owner)', 'owner@coldconnect.test', '$2y$10$YmPFiIdty9qtJ/FzOnJJZe3KJQ7NJ9HkRy..FdAyO9i7hxbrdZUHO', '+91 98250 11223', 'owner', 'Ahmedabad', NOW()),
(3, 'Mahesh Choudhary', 'mahesh.owner@coldconnect.test', '$2y$10$YmPFiIdty9qtJ/FzOnJJZe3KJQ7NJ9HkRy..FdAyO9i7hxbrdZUHO', '+91 98251 44556', 'owner', 'Vadodara', NOW()),
(4, 'Govind Bhai Rabari', 'govind.farmer@coldconnect.test', '$2y$10$ZNESvTtsEi4o3zxHOjBx2OsVOY4QUHq2OisOv6yKB7jP.nvZbmIsK', '+91 98980 99887', 'farmer', 'Surat', NOW());

-- ----------------------------------------------------------
-- 5. Cold Storages Demo Data across Gujarat Hubs
-- ----------------------------------------------------------
INSERT INTO `cold_storages` (`id`, `owner_id`, `name`, `location`, `available_capacity`, `total_capacity`, `temperature`, `price_per_kg`, `supported_crops`, `minimum_quantity`, `contact`, `status`, `created_at`) VALUES
(1, 2, 'Shree Cold Storage', 'Ahmedabad', 800.00, 2000.00, '2°C - 8°C', 2.00, 'Tomato, Potato, Onion, Apple', 100.00, '+91 98250 11223', 'Available', NOW()),
(2, 3, 'Baroda Fresh Agro Vault', 'Vadodara', 1200.00, 2500.00, '4°C - 10°C', 2.30, 'Tomato, Potato, Mango, Onion', 50.00, '+91 98251 44556', 'Available', NOW()),
(3, 2, 'Surat Diamond Agro Chillers', 'Surat', 1500.00, 3500.00, '1°C - 7°C', 2.20, 'Potato, Onion, Tomato, Banana', 100.00, '+91 98252 77889', 'Available', NOW()),
(4, 2, 'Rajkot Saurashtra Cold Hub', 'Rajkot', 3000.00, 6000.00, '0°C - 5°C', 1.90, 'Apple, Potato, Carrot, Tomato', 200.00, '+91 98253 99001', 'Available', NOW()),
(5, 3, 'Sardar Patel Warehouse', 'Anand', 1800.00, 4000.00, '3°C - 9°C', 2.10, 'Onion, Garlic, Tomato, Potato', 150.00, '+91 98254 33221', 'Available', NOW()),
(6, 2, 'Gujarat Agro Cold Preserve', 'Ahmedabad', 900.00, 2000.00, '1°C - 6°C', 2.80, 'Mango, Apple, Strawberry, Tomato', 50.00, '+91 98255 66778', 'Available', NOW());

-- ----------------------------------------------------------
-- 6. Sample Initial Bookings Demo Data (with Insurance & Spoilage Demo)
-- ----------------------------------------------------------
INSERT INTO `bookings` (`id`, `farmer_id`, `storage_id`, `pickup_location`, `crop`, `quantity`, `start_date`, `end_date`, `total_cost`, `has_insurance`, `insurance_rate`, `insurance_fee`, `damage_status`, `damage_cause`, `affected_crop`, `affected_quantity`, `compensation_amount`, `status`, `created_at`) VALUES
(1, 1, 2, 'Ahmedabad', 'Potato', 300.00, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 11 DAY), 7245.00, 1, 85.00, 345.00, 'None', NULL, NULL, NULL, NULL, 'Accepted', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 4, 1, 'Surat', 'Onion', 250.00, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 16 DAY), 7350.00, 1, 85.00, 350.00, 'Reported', 'Chamber Compressor Failure & Refrigerant Gas Leak', 'Onion', 200.00, 5950.00, 'Accepted', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 1, 1, 'Ahmedabad', 'Tomato', 400.00, DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY), 9600.00, 0, 85.00, 0.00, 'None', NULL, NULL, NULL, NULL, 'Pending', DATE_SUB(NOW(), INTERVAL 1 HOUR));
