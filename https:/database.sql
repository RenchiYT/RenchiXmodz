-- database.sql
CREATE DATABASE IF NOT EXISTS proxy_panel;
USE proxy_panel;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discord_id VARCHAR(64) UNIQUE,
    username VARCHAR(100),
    email VARCHAR(255),
    password_hash VARCHAR(255),
    auth_method ENUM('discord', 'google', 'custom') DEFAULT 'custom',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE proxy_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    freefire_uid VARCHAR(20) NOT NULL,
    proxy_token VARCHAR(64) UNIQUE NOT NULL,
    assigned_server_id INT,
    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE proxy_servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    ip VARCHAR(45) NOT NULL,
    port INT NOT NULL DEFAULT 1080,
    location VARCHAR(100),
    country_code VARCHAR(5),
    max_users INT DEFAULT 100,
    current_users INT DEFAULT 0,
    status ENUM('online', 'offline', 'maintenance') DEFAULT 'online'
);

INSERT INTO proxy_servers (name, ip, port, location, country_code) VALUES
('US West', 'YOUR_VPS_IP_1', 1080, 'United States', 'US'),
('Europe', 'YOUR_VPS_IP_2', 1080, 'Germany', 'EU'),
('Singapore', 'YOUR_VPS_IP_3', 1080, 'Singapore', 'SG');
