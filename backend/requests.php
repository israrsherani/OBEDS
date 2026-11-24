<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/db.php';

// Login protection
if (!isset($_SESSION['userId'])) {
    header("Location: ../frontend/login.html");
    exit;
}

$userId = (int) $_SESSION['userId'];

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch Incoming Requests
$stmt = $conn->prepare(
    "SELECT
        requests.requestId,
        requests.status,
        books.title,
        users.name
     FROM requests
     JOIN books ON requests.bookId = books.bookId
     JOIN users ON requests.requesterId = users.userId
     WHERE requests.ownerId = ?
     ORDER BY requests.createdAt DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$incomingRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch My Sent Requests
$stmt = $conn->prepare(
    "SELECT
        requests.requestId,
        requests.status,
        books.title,
        users.name AS ownerName
     FROM requests
     JOIN books ON requests.bookId = books.bookId
     JOIN users ON requests.ownerId = users.userId
     WHERE requests.requesterId = ?
     ORDER BY requests.createdAt DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$sentRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Shared header settings
$base = '../';
$pageTitle = 'My Requests';
$active = 'requests';

require_once __DIR__ . '/../includes/header.php';

// Request status styling
function request_status_class($status) {
    switch (strtolower(trim((string) $status))) {
        case 'accepted':
            return 'status-accepted';

        case 'rejected':
            return 'status-rejected';

        default:
            return 'status-pending';
    }
}
?>

<div class="page-head">
    <div>
        <h1 class="page-title">My Requests</h1>
        <p>Manage the book requests you receive and track the requests you send.</p>
    </div>
</div>

<!-- Incoming Requests -->
<section class="req-section">

    <h2>Incoming Requests</h2>

    <?php if (empty($incomingRequests)): ?>

        <div class="empty">
            <h3>No incoming requests</h3>
            <p>You have not received any book requests yet.</p>
        </div>

    <?php else: ?>

        <div class="req-grid">

            <?php foreach ($incomingRequests as $row): ?>

                <article class="req-card">

                    <h3><?= e($row['title']) ?></h3>

                    <p><strong>Requested by:</strong> <?= e($row['name']) ?></p>

                    <p><strong>Request ID:</strong> #<?= (int) $row['requestId'] ?></p>

                    <span class="status <?= request_status_class($row['status']) ?>">
                        <?= e($row['status']) ?>
                    </span>

                    <?php if (strtolower(trim((string) $row['status'])) === 'pending'): ?>

                        <form method="POST" action="update_request.php" class="req-actions">

                            <input type="hidden" name="requestId" value="<?= (int) $row['requestId'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                            <button type="submit" name="action" value="accept" class="btn gold">
                                Accept
                            </button>

                            <button type="submit" name="action" value="reject" class="btn btn-reject">
                                Reject
                            </button>

                        </form>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<!-- My Sent Requests -->
<section class="req-section">

    <h2>My Sent Requests</h2>

    <?php if (empty($sentRequests)): ?>

        <div class="empty">
            <h3>No sent requests</h3>
            <p>You have not requested any books yet.</p>
        </div>

    <?php else: ?>

        <div class="req-grid">

            <?php foreach ($sentRequests as $row): ?>

                <article class="req-card">

                    <h3><?= e($row['title']) ?></h3>

                    <p><strong>Book owner:</strong> <?= e($row['ownerName']) ?></p>

                    <p><strong>Request ID:</strong> #<?= (int) $row['requestId'] ?></p>

                    <span class="status <?= request_status_class($row['status']) ?>">
                        <?= e($row['status']) ?>
                    </span>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>