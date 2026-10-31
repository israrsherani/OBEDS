-- OBEDS: List-a-book feature (books table only)
-- If this database already exists in phpMyAdmin, remove the CREATE DATABASE line.
CREATE DATABASE IF NOT EXISTS obeds CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE obeds;

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                 -- owner (will link to users.id once login is built)
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    isbn VARCHAR(20) DEFAULT NULL,
    category VARCHAR(80) NOT NULL,
    book_condition ENUM('new','good','fair','poor') NOT NULL DEFAULT 'good',
    listing_type ENUM('exchange','donate') NOT NULL DEFAULT 'exchange',
    description TEXT,
    cover_image VARCHAR(255) DEFAULT NULL,
    status ENUM('available','exchanged','donated') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
