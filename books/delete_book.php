<?php
require __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list_books.php');
    exit;
}

$bookId = (int)($_POST['book_id'] ?? 0);

$stmt = $pdo->prepare('SELECT ownerId, cover_image FROM books WHERE bookId = ?');
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: list_books.php?msg=notfound');
    exit;
}

if (!can_delete_book($book['ownerId'])) {
    header('Location: list_books.php?msg=notallowed');
    exit;
}

$pdo->prepare('DELETE FROM books WHERE bookId = ?')->execute([$bookId]);

header('Location: list_books.php?msg=deleted');
exit;