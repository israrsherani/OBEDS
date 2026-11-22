
<?php
session_start();
require_once __DIR__ . '/db.php';

function loginRedirect($error) {
    header("Location: ../frontend/login.html?error=" . urlencode($error));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    loginRedirect("invalid_request");
}

$email = trim($_POST["email"] ?? '');
$password = $_POST["password"] ?? '';

if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    loginRedirect("invalid_credentials");
}

$stmt = $conn->prepare(
    "SELECT userId, name, passwordHash, role, isSuspended
     FROM users
     WHERE email = ?"
);

if (!$stmt) {
    error_log("Login prepare failed: " . $conn->error);
    loginRedirect("login_failed");
}

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {

    $user = $result->fetch_assoc();

    if (password_verify($password, $user["passwordHash"])) {

        if ((int)$user["isSuspended"] === 1) {
            $stmt->close();
            $conn->close();
            loginRedirect("suspended");
        }

        session_regenerate_id(true);

        $_SESSION["userId"] = (int)$user["userId"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["role"] = $user["role"];

        $stmt->close();
        $conn->close();

        header("Location: ../index.php");
        exit;
    }
}

$stmt->close();
$conn->close();

loginRedirect("invalid_credentials");
?>
