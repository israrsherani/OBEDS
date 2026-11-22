
<?php

require_once __DIR__ . '/../includes/db.php';

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list_books.php');
    exit;
}

// Login protection
if (current_user_id() === null) {
    header('Location: ../frontend/login.html');
    exit;
}

// CSRF protection
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header('Location: list_books.php?msg=invalid_token');
    exit;
}

// Validate book ID
$bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);

if (!$bookId || $bookId <= 0) {
    header('Location: list_books.php?msg=notfound');
    exit;
}

try {

    // Find book and verify ownership
    $stmt = $pdo->prepare(
        "SELECT ownerId
         FROM books
         WHERE bookId = ?"
    );

    $stmt->execute([$bookId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        header('Location: list_books.php?msg=notfound');
        exit;
    }

    // Owner or admin permission check
    if (!can_delete_book($book['ownerId'])) {
        header('Location: list_books.php?msg=notallowed');
        exit;
    }

    // Delete book safely
    $pdo->beginTransaction();

    // Remove requests associated with this book
    $stmt = $pdo->prepare(
        "DELETE FROM requests WHERE bookId = ?"
    );

    $stmt->execute([$bookId]);

    // Delete the book
    $stmt = $pdo->prepare(
        "DELETE FROM books
         WHERE bookId = ?
           AND ownerId = ?"
    );

    $stmt->execute([
        $bookId,
        $book['ownerId']
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception("Book could not be deleted.");
    }

    $pdo->commit();

    header('Location: list_books.php?msg=deleted');
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("OBEDS book deletion error: " . $e->getMessage());

    header('Location: list_books.php?msg=delete_failed');
    exit;
}

?>
