<?php
// Start the session once (needed to know who is logged in)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// XAMPP default: user = root, password = empty
$host = 'localhost';
$db   = 'obeds';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Makes text safe to show on a page (stops XSS attacks)
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// ---------- User helpers ----------
// TODO: when the login feature is built, the login page must set
//   $_SESSION['user_id'] and $_SESSION['role'] ('admin' or 'user').
// Until then we pretend user 1 is logged in.

function current_user_id() {
    return $_SESSION['user_id'] ?? 1;
}

function is_admin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

// A book can be deleted by its owner, or by an admin
function can_delete_book($ownerId) {
    return is_admin() || (int)$ownerId === (int)current_user_id();
}
