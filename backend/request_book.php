
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/db.php";

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

$bookId = filter_var($_POST['bookId'], FILTER_VALIDATE_INT);

if (!$bookId || $bookId <= 0) {
    goBack("invalid_book");
}

try {

    $conn->begin_transaction();

    // Fetch and lock book row
    $stmt = $conn->prepare(
        "SELECT ownerId, status
         FROM books
         WHERE bookId = ?
         FOR UPDATE"
    );

    $stmt->bind_param("i", $bookId);
    $stmt->execute();

    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$book || $book['ownerId'] === null) {
        throw new Exception("unavailable");
    }

    if (strtolower(trim($book['status'])) !== 'available') {
        throw new Exception("unavailable");
    }

    $ownerId = (int) $book['ownerId'];

    // Prevent requesting own book
    if ($ownerId === $requesterId) {
        throw new Exception("own_book");
    }

    // Check existing pending request
    $stmt = $conn->prepare(
        "SELECT requestId
         FROM requests
         WHERE bookId = ?
         AND requesterId = ?
         AND status = 'Pending'
         LIMIT 1"
    );

    $stmt->bind_param("ii", $bookId, $requesterId);
    $stmt->execute();

    $existingRequest = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existingRequest) {
        throw new Exception("already_pending");
    }

    // Insert new request
    $stmt = $conn->prepare(
        "INSERT INTO requests (bookId, requesterId, ownerId)
         VALUES (?, ?, ?)"
    );

    $stmt->bind_param("iii", $bookId, $requesterId, $ownerId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    goBack("requested");

} catch (Exception $e) {

    $conn->rollback();

    $message = $e->getMessage();

    $allowedMessages = [
        "unavailable",
        "own_book",
        "already_pending"
    ];

    if (!in_array($message, $allowedMessages, true)) {
        $message = "request_failed";
    }

    goBack($message);
}
?>
