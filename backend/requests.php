
<?php
session_start();

// MySQLi connection for requests
require __DIR__ . '/db.php';

// Shared PDO connection and helper functions for header
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['userId'])) {
    header("Location: ../frontend/login.html");
    exit;
}

$userId = (int) $_SESSION['userId'];

// Fetch Incoming Requests
$stmt = $conn->prepare(
    "SELECT requests.requestId, requests.status,
            books.title, users.name
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
    "SELECT requests.requestId, requests.status,
            books.title, users.name AS ownerName
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

// Shared page layout
$base = '../';
$pageTitle = 'My Requests';
$active = '';

require __DIR__ . '/../includes/header.php';

// Status styling
function request_status_class($status) {
    switch (strtolower($status)) {
        case 'accepted':
            return 'status-accepted';

        case 'rejected':
            return 'status-rejected';

        default:
            return 'status-pending';
    }
}
?>

<style>
.requests-page {
    padding: 35px 0 55px;
}

.requests-heading {
    margin-bottom: 28px;
}

.requests-heading h1 {
    margin-bottom: 8px;
}

.requests-heading p {
    color: #687386;
    margin: 0;
}

.request-section {
    margin-bottom: 42px;
}

.request-section h2 {
    margin-bottom: 18px;
    font-size: 23px;
}

.request-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
}

.request-card {
    background: #fff;
    border: 1px solid #e4e8ef;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
}

.request-card h3 {
    font-size: 18px;
    margin: 0 0 12px;
    color: #202b3c;
}

.request-card p {
    color: #647084;
    margin: 8px 0;
    line-height: 1.5;
}

.request-card strong {
    color: #293548;
}

.request-status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    margin-top: 12px;
}

.status-pending {
    background: #fff4d6;
    color: #946200;
}

.status-accepted {
    background: #dcfce7;
    color: #166534;
}

.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.request-actions {
    display: flex;
    gap: 10px;
    margin-top: 18px;
    flex-wrap: wrap;
}

.request-actions button {
    border: none;
    border-radius: 7px;
    padding: 10px 18px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
}

.accept-btn {
    background: #16834a;
    color: white;
}

.accept-btn:hover {
    background: #11683a;
}

.reject-btn {
    background: #dc3545;
    color: white;
}

.reject-btn:hover {
    background: #b92534;
}

.empty-requests {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 28px 20px;
    text-align: center;
    color: #687386;
}

@media (max-width: 600px) {
    .requests-page {
        padding-top: 24px;
    }

    .request-card {
        padding: 18px;
    }
}
</style>

<div class="requests-page">

    <div class="requests-heading">
        <h1>My Requests</h1>
        <p>Manage the book requests you receive and track the requests you send.</p>
    </div>

    <!-- Incoming Requests -->
    <section class="request-section">

        <h2>Incoming Requests</h2>

        <?php if (empty($incomingRequests)): ?>

            <div class="empty-requests">
                <h3>No incoming requests</h3>
                <p>You have not received any book requests yet.</p>
            </div>

        <?php else: ?>

            <div class="request-grid">

                <?php foreach ($incomingRequests as $row): ?>

                    <article class="request-card">

                        <h3><?= htmlspecialchars($row['title']) ?></h3>

                        <p>
                            <strong>Requested by:</strong>
                            <?= htmlspecialchars($row['name']) ?>
                        </p>

                        <p>
                            <strong>Request ID:</strong>
                            #<?= (int) $row['requestId'] ?>
                        </p>

                        <span class="request-status <?= request_status_class($row['status']) ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>

                        <?php if ($row['status'] === 'Pending'): ?>

                            <form
                                method="POST"
                                action="update_request.php"
                                class="request-actions"
                            >

                                <input
                                    type="hidden"
                                    name="requestId"
                                    value="<?= (int) $row['requestId'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="action"
                                    value="accept"
                                    class="accept-btn"
                                >
                                    Accept
                                </button>

                                <button
                                    type="submit"
                                    name="action"
                                    value="reject"
                                    class="reject-btn"
                                >
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
    <section class="request-section">

        <h2>My Sent Requests</h2>

        <?php if (empty($sentRequests)): ?>

            <div class="empty-requests">
                <h3>No sent requests</h3>
                <p>You have not requested any books yet.</p>
            </div>

        <?php else: ?>

            <div class="request-grid">

                <?php foreach ($sentRequests as $row): ?>

                    <article class="request-card">

                        <h3><?= htmlspecialchars($row['title']) ?></h3>

                        <p>
                            <strong>Book owner:</strong>
                            <?= htmlspecialchars($row['ownerName']) ?>
                        </p>

                        <p>
                            <strong>Request ID:</strong>
                            #<?= (int) $row['requestId'] ?>
                        </p>

                        <span class="request-status <?= request_status_class($row['status']) ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
