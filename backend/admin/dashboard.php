
<?php
require_once "../../includes/db.php";

// Only admins can access this page
if (!is_admin()) {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

// Dashboard statistics
$totalUsers = $pdo->query(
    "SELECT COUNT(*) FROM users"
)->fetchColumn();

$totalBooks = $pdo->query(
    "SELECT COUNT(*) FROM books"
)->fetchColumn();

$availableBooks = $pdo->query(
    "SELECT COUNT(*) FROM books WHERE status = 'Available'"
)->fetchColumn();

$pendingRequests = $pdo->query(
    "SELECT COUNT(*) FROM requests WHERE status = 'Pending'"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OBEDS</title>
    <link rel="stylesheet" href="/OBEDS/style.css?v=2">
</head>
<body>

<h1>OBEDS Admin Dashboard</h1>

<p>Welcome, <?= e($_SESSION['name'] ?? 'Admin') ?>!</p>
<div class="admin-actions">
    <a class="btn" href="users.php">Manage Users</a>
    <a class="btn" href="books.php">Manage Books</a>
</div>

<div class="admin-stats">
    <div>
        <h2><?= $totalUsers ?></h2>
        <p>Total Users</p>
    </div>

    <div>
        <h2><?= $totalBooks ?></h2>
        <p>Total Books</p>
    </div>

    <div>
        <h2><?= $availableBooks ?></h2>
        <p>Available Books</p>
    </div>

    <div>
        <h2><?= $pendingRequests ?></h2>
        <p>Pending Requests</p>
    </div>
</div>

</body>
</html>
