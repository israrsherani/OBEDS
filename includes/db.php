<?php

// Start the session once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection settings
$host = 'localhost';
$db   = 'obeds-db';
$user = 'root';
$pass = '';

// Connect to the database
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Please check the database settings.');
}

// Make text safe before displaying it
function e($v) {
    return htmlspecialchars(
        (string)$v,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Get the logged-in user's ID
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Check whether the logged-in user is an admin
function is_admin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

// Check whether a user can delete a book
function can_delete_book($ownerId) {

    // Admin can delete any book
    if (is_admin()) {
        return true;
    }

    // Normal user can delete only their own book
    return current_user_id() !== null
        && (int)$ownerId === (int)current_user_id();
}