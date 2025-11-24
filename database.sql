-- database.sql
-- MySQL Database Schema for GuviHCL Internship Authentication System

-- Create database
CREATE DATABASE IF NOT EXISTS internship_app
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE internship_app;

-- Drop existing table if exists (for clean setup)
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for faster queries
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample user for testing (password: test123)
INSERT INTO users (email, username, password_hash, created_at) VALUES
('test@example.com', 'testuser', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5lNjPXr8Y8lVe', NOW());

-- Verify table structure
DESCRIBE users;

-- Display success message
SELECT 'Database and table created successfully!' AS Status;