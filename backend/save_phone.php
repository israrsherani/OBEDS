<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Login protection
if (!isset($_SESSION['userId'])) {
    header("Location: ../frontend/login.html");
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: requests.php");
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    header("Location: requests.php?msg=invalid_token");
    exit;
}

$userId = (int) $_SESSION['userId'];
$phone  = trim((string) ($_POST['phone'] ?? ''));

// Validate: optional leading +, then digits/spaces/dashes, 7-15 digits total
$digits = preg_replace('/\D+/', '', $phone);

if (
    !preg_match('/^\+?[0-9 \-]{7,20}$/', $phone)
    || strlen($digits) < 7
    || strlen($digits) > 15
) {
    header("Location: requests.php?msg=phone_invalid");
    exit;
}

$stmt = $conn->prepare("UPDATE users SET phone = ? WHERE userId = ?");

if (!$stmt) {
    header("Location: requests.php?msg=phone_failed");
    exit;
}

$stmt->bind_param("si", $phone, $userId);
$ok = $stmt->execute();
$stmt->close();

header("Location: requests.php?msg=" . ($ok ? 'phone_saved' : 'phone_failed'));
exit;