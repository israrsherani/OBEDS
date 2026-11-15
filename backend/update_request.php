<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "db.php";

if (!isset($_SESSION['userId'])) {
    die("Please login first.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

$requestId = (int)($_POST['requestId'] ?? 0);
$action = $_POST['action'] ?? '';
$ownerId = (int)$_SESSION['userId'];

if ($requestId <= 0 || !in_array($action, ['accept', 'reject'], true)) {
    die("Invalid request details.");
}

$conn->begin_transaction();

try {

    // Verify that this request belongs to the logged-in owner
    $stmt = $conn->prepare(
        "SELECT r.bookId, b.status
         FROM requests r
         JOIN books b ON r.bookId = b.bookId
         WHERE r.requestId = ?
         AND r.ownerId = ?
         AND r.status = 'Pending'
         FOR UPDATE"
    );

    $stmt->bind_param("ii", $requestId, $ownerId);
    $stmt->execute();

    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$request) {
        throw new Exception("Request not found or not authorized.");
    }

    if ($action === 'accept') {

        if ($request['status'] !== 'Available') {
            throw new Exception("Book is no longer available.");
        }

        $stmt = $conn->prepare(
            "UPDATE books
             SET status = 'Not Available'
             WHERE bookId = ? AND ownerId = ? AND status = 'Available'"
        );

        $stmt->bind_param("ii", $request['bookId'], $ownerId);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            throw new Exception("Could not update book status.");
        }

        $stmt->close();

        $newStatus = 'Accepted';

    } else {

        $newStatus = 'Rejected';

    }

    $stmt = $conn->prepare(
        "UPDATE requests
         SET status = ?
         WHERE requestId = ? AND ownerId = ? AND status = 'Pending'"
    );

    $stmt->bind_param("sii", $newStatus, $requestId, $ownerId);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception("Could not update request.");
    }

    $stmt->close();

    $conn->commit();

    header("Location: requests.php?msg=updated");
    exit;

} catch (Exception $e) {

    $conn->rollback();
    die($e->getMessage());

}
?>