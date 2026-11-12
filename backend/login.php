
<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare(
        "SELECT userId, name, password FROM users WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            $_SESSION["userId"] = $user["userId"];
            $_SESSION["name"] = $user["name"];

            echo "Login successful! Welcome " .
                 htmlspecialchars($user["name"]);

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
