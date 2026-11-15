
<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare(
        "SELECT userId, name, passwordHash, role, isSuspended
         FROM users WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["passwordHash"])) {

            // Block suspended accounts
            if ((int)$user["isSuspended"] === 1) {
                echo "Your account has been suspended.";
                $stmt->close();
                $conn->close();
                exit;
            }

            // Start a fresh session after login
            session_regenerate_id(true);

            $_SESSION["userId"] = $user["userId"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            $stmt->close();
            $conn->close();

            header("Location: ../index.php");
            exit;

        } else {
            echo "Invalid email or password.";
        }

    } else {
        echo "Invalid email or password.";
    }

    $stmt->close();
}

$conn->close();
?>
