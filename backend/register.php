<?php
require_once __DIR__ . "/db.php";

function registerRedirect($error) {
    header("Location: ../frontend/register.html?error=" . urlencode($error));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    registerRedirect("invalid_request");
}

$name = trim($_POST["fullname"] ?? '');
$email = trim($_POST["email"] ?? '');
$phone = trim($_POST["phone"] ?? '');
$password = $_POST["password"] ?? '';
$confirmPassword = $_POST["confirm-password"] ?? '';

// Validate name
if ($name === '' || strlen($name) > 100) {
    registerRedirect("invalid_name");
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    registerRedirect("invalid_email");
}

// Validate phone (spaces and dashes allowed, 10-15 digits, optional +)
$phone = preg_replace('/[\s\-()]/', '', $phone);
if (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
    registerRedirect("invalid_phone");
}

// Validate password
if (strlen($password) < 8) {
    registerRedirect("weak_password");
}

// Confirm password
if ($password !== $confirmPassword) {
    registerRedirect("password_mismatch");
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare(
    "INSERT INTO users (name, email, phone, passwordHash)
     VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    error_log("Registration prepare failed: " . $conn->error);
    $conn->close();
    registerRedirect("registration_failed");
}

$stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

if ($stmt->execute()) {

    $stmt->close();
    $conn->close();

    header("Location: ../frontend/register.html?registered=1");
    exit;

} else {

    $errorCode = ($stmt->errno === 1062)
        ? "email_exists"
        : "registration_failed";

    error_log("Registration failed: " . $stmt->error);

    $stmt->close();
    $conn->close();

    registerRedirect($errorCode);
}
?>