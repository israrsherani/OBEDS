
<?php
require_once "../../includes/db.php";

// Admin access protection
if (!is_admin()) {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle book deletion
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        exit("Invalid request.");
    }

    $bookId = (int)($_POST['bookId'] ?? 0);

    if ($bookId > 0) {
        $stmt = $pdo->prepare(
            "DELETE FROM books WHERE bookId = ?"
        );
        $stmt->execute([$bookId]);
    }

    header("Location: books.php?status=updated");
    exit;
}

// Fetch all books
$stmt = $pdo->query(
    "SELECT 
        b.bookId,
        b.title,
        b.author,
        u.name AS ownerName,
        u.email AS ownerEmail,
        b.category,
        b.status,
        b.listing_type,
        b.created_at
     FROM books b
     LEFT JOIN users u ON b.ownerId = u.userId
     ORDER BY b.bookId DESC"
);

$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Books - OBEDS</title>
<link rel="stylesheet" href="../../style.css">
</head>

<body>

<main class="wrap">

    <div class="page-head">
        <h1>Manage Books</h1>
        <a class="btn ghost" href="dashboard.php">
            Back to Dashboard
        </a>
    </div>

    <?php if (isset($_GET['status'])): ?>
        <p class="notice ok">Book deleted successfully.</p>
    <?php endif; ?>

    <p class="admin-users-count">
        Total Books: <?= count($books) ?>
    </p>

    <?php if (empty($books)): ?>

        <div class="empty">No books found.</div>

    <?php else: ?>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Owner Name</th>
<th>Owner Email</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td><?= e($book['bookId']) ?></td>
                    <td><?= e($book['title']) ?></td>
                    <td><?= e($book['author']) ?></td>
                    <td><?= e($book['ownerName'] ?? 'Unknown') ?></td>
<td><?= e($book['ownerEmail'] ?? 'Unknown') ?></td>
                    <td><?= e($book['category']) ?></td>
                    <td><?= e($book['listing_type']) ?></td>
                    <td><?= e($book['status']) ?></td>

                    <td>
                        <form method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this book?');">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?= e($_SESSION['csrf_token']) ?>">

                            <input type="hidden"
                                   name="bookId"
                                   value="<?= e($book['bookId']) ?>">

                            <button type="submit" class="btn danger">
                                Delete
                            </button>

                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

</main>

</body>
</html>
