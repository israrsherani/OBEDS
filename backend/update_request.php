
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/db.php";

// Login protection
if (!isset($_SESSION['userId'])) {
    header("Location: ../frontend/login.html");
    exit;
}

// Only POST requests allowed
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

// CSRF protection
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    die("Invalid security token. Please go back and try again.");
}

// Request details
$requestId = filter_input(INPUT_POST, 'requestId', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$ownerId = (int) $_SESSION['userId'];

if (
    !$requestId ||
    $requestId <= 0 ||
    !in_array($action, ['accept', 'reject'], true)
) {
    die("Invalid request details.");
}

$conn->begin_transaction();

try {

    // Fetch request and verify ownership
    // Lock the request and associated book during processing
    $stmt = $conn->prepare(
        "SELECT
            r.bookId,
            r.status AS requestStatus,
            b.status AS bookStatus
         FROM requests r
         INNER JOIN books b ON r.bookId = b.bookId
         WHERE r.requestId = ?
           AND r.ownerId = ?
           AND b.ownerId = ?
         FOR UPDATE"
    );

    $stmt->bind_param("iii", $requestId, $ownerId, $ownerId);
    $stmt->execute();

    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$request) {
        throw new Exception("Request not found or you are not authorized.");
    }

    if (strtolower(trim($request['requestStatus'])) !== 'pending') {
        throw new Exception("This request has already been processed.");
    }

    // Accept request
    if ($action === 'accept') {

        if (strtolower(trim($request['bookStatus'])) !== 'available') {
            throw new Exception("This book is no longer available.");
        }

        // Mark book as unavailable
        $stmt = $conn->prepare(
            "UPDATE books
             SET status = 'Not Available'
             WHERE bookId = ?
               AND ownerId = ?
               AND LOWER(status) = 'available'"
        );

        $stmt->bind_param("ii", $request['bookId'], $ownerId);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            throw new Exception("Could not update book availability.");
        }

        $stmt->close();

        $newStatus = 'Accepted';

    } else {

        // Reject request
        $newStatus = 'Rejected';

    }

    // Update selected request
    $stmt = $conn->prepare(
        "UPDATE requests
         SET status = ?
         WHERE requestId = ?
           AND ownerId = ?
           AND LOWER(status) = 'pending'"
    );

    $stmt->bind_param("sii", $newStatus, $requestId, $ownerId);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception("Could not update request status.");
    }

    $stmt->close();

    // If accepted, reject all other pending requests
    // for the same book
    if ($action === 'accept') {

        $stmt = $conn->prepare(
            "UPDATE requests
             SET status = 'Rejected'
             WHERE bookId = ?
               AND ownerId = ?
               AND requestId != ?
               AND LOWER(status) = 'pending'"
        );

        $stmt->bind_param(
            "iii",
            $request['bookId'],
            $ownerId,
            $requestId
        );

        $stmt->execute();
        $stmt->close();
    }

    // Save all database changes
    $conn->commit();

    header("Location: requests.php?msg=updated");
    exit;

} catch (Exception $e) {

    $conn->rollback();

    http_response_code(400);

    die(
        "Request could not be processed: " .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    );
}

?>
