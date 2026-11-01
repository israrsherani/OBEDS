<?php
require __DIR__ . '/../includes/db.php';

// Deleting is allowed only from the form button (POST), not by typing the address
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list_books.php');
    exit;
}

$bookId = (int) ($_POST['book_id'] ?? 0);

// Find the book first
$stmt = $pdo->prepare('SELECT user_id, cover_image FROM books WHERE id = ?');
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: list_books.php?msg=notfound');
    exit;
}

// Security check: only the owner or an admin may delete.
// The button is hidden for others, but we check again here
// because someone could try to send the request manually.
if (!can_delete_book($book['user_id'])) {
    header('Location: list_books.php?msg=notallowed');
    exit;
}

// Delete the book from the database
$pdo->prepare('DELETE FROM books WHERE id = ?')->execute([$bookId]);

// Also remove its cover image file, if it has one
if ($book['cover_image']) {
    $path = __DIR__ . '/../assets/uploads/books/' . basename($book['cover_image']);
    if (is_file($path)) {
        unlink($path);
    }
}

header('Location: list_books.php?msg=deleted');
exit;
