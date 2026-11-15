
<?php
session_start();
include "db.php";

// Redirect helper
function goBack($message) {
    global $conn;

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }

    header("Location: ../books/list_books.php?msg=" . urlencode($message));
    exit;
}

// Check login
if (!isset($_SESSION['userId'])) {
    goBack("login_required");
}

$requesterId = (int) $_SESSION['userId'];

// Check request method
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['bookId'])) {
    goBack("invalid_request");
}

$bookId = (int) $_POST['bookId'];

if ($bookId <= 0) {
    goBack("invalid_book");
}

// Check book availability
$stmt = $conn->prepare(
    "SELECT ownerId FROM books
     WHERE bookId = ? AND status = 'Available'"
);

$stmt->bind_param("i", $bookId);
$stmt->execute();

$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book || $book['ownerId'] === null) {
    goBack("unavailable");
}

$ownerId = (int) $book['ownerId'];

// Prevent requesting own book
if ($ownerId === $requesterId) {
    goBack("own_book");
}

// Check existing pending request
$stmt = $conn->prepare(
    "SELECT requestId FROM requests
     WHERE bookId = ? AND requesterId = ?
     AND status = 'Pending'"
);

$stmt->bind_param("ii", $bookId, $requesterId);
$stmt->execute();

$existingRequest = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existingRequest) {
    goBack("already_pending");
}

// Insert new request
$stmt = $conn->prepare(
    "INSERT INTO requests (bookId, requesterId, ownerId)
     VALUES (?, ?, ?)"
);

$stmt->bind_param("iii", $bookId, $requesterId, $ownerId);
$stmt->execute();
$stmt->close();

goBack("requested");
?>
