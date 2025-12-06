-- MySQL Database Setup Script for HSE Induction Platform
-- Run this script to create the database

-- Create the database
CREATE DATABASE IF NOT EXISTS hse_induction 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Optional: Create a dedicated user (recommended for production)
-- Uncomment and modify the following lines if you want to use a dedicated user
-- CREATE USER IF NOT EXISTS 'hse_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
-- GRANT ALL PRIVILEGES ON hse_induction.* TO 'hse_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Show success message
SELECT 'Database hse_induction created successfully!' AS message;

