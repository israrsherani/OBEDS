
<?php

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm-password"];

    // Check password confirmation
    if ($password !== $confirmPassword) {
        die("Passwords do not match.");
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }

    // Check password length
    if (strlen($password) < 8) {
        die("Password must be at least 8 characters.");
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, passwordHash)
         VALUES (?, ?, ?)"
    );

    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    if ($stmt->execute()) {

        // Redirect to login page after successful registration
        header("Location: ../frontend/login.html?registered=1");
        exit;

    } else {

        if ($conn->errno == 1062) {
            echo "This email is already registered.";
        } else {
            echo "Registration failed. Please try again.";
        }
    }

    $stmt->close();

} else {
    echo "Invalid request.";
}

$conn->close();

?>
