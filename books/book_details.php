<?php
require_once __DIR__ . '/../includes/db.php';

$bookId = $_GET['id'] ?? '';

if (!filter_var($bookId, FILTER_VALIDATE_INT)) {
    die("Invalid book ID.");
}

$stmt = $pdo->prepare("SELECT * FROM books WHERE bookId = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    die("Book not found.");
}

function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Details - OBEDS</title>
</head>
<body>

<h1>Book Details</h1>

<h2><?= safe($book['title']) ?></h2>

<p><b>Author:</b> <?= safe($book['author']) ?></p>
<p><b>ISBN:</b> <?= safe($book['isbn']) ?></p>
<p><b>Category:</b> <?= safe($book['category']) ?></p>
<p><b>Condition:</b> <?= safe($book['book_condition']) ?></p>
<p><b>Listing Type:</b> <?= safe($book['listing_type']) ?></p>
<p><b>Description:</b> <?= safe($book['description']) ?></p>
<form method="POST" action="../backend/request_book.php">
    <input type="hidden" name="bookId"
           value="<?= safe($book['bookId']) ?>">
    <button type="submit">Request this book</button>
</form>

<a href="search_books.php">Back to Search Books</a>

</body>
</html>