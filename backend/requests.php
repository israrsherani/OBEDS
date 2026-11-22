
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

<style>
.requests-page {
    padding: 40px 0 60px;
}

.requests-heading {
    margin-bottom: 32px;
}

.requests-heading h1 {
    color: #1D3557;
    font-size: 32px;
    margin: 0 0 10px;
    font-weight: 700;
}

.requests-heading p {
    color: #6B7280;
    font-size: 15px;
    margin: 0;
    line-height: 1.7;
}

.request-section {
    margin-bottom: 45px;
}

.request-section h2 {
    color: #1D3557;
    font-size: 23px;
    font-weight: 700;
    margin: 0 0 20px;
}

.request-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(270px, 360px));
    gap: 22px;
}

.request-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 4px 14px rgba(29, 53, 87, 0.05);
    transition: background 0.2s ease, transform 0.2s ease,
                box-shadow 0.2s ease;
}

.request-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(29, 53, 87, 0.09);
}

.request-card h3 {
    font-size: 19px;
    font-weight: 700;
    color: #1D3557;
    margin: 0 0 16px;
    line-height: 1.5;
    word-break: break-word;
}

.request-card p {
    color: #6B7280;
    margin: 10px 0;
    font-size: 14px;
    line-height: 1.6;
}

.request-card strong {
    color: #1F2937;
    font-weight: 600;
}

.request-status {
    display: inline-flex;
    align-items: center;
    padding: 7px 13px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    text-transform: capitalize;
    margin-top: 12px;
}

.status-pending {
    background: #FFF4D6;
    color: #946200;
}

.status-accepted {
    background: #DCFCE7;
    color: #166534;
}

.status-rejected {
    background: #FEE2E2;
    color: #991B1B;
}

.request-actions {
    display: flex;
    gap: 12px;
    margin-top: 22px;
    flex-wrap: wrap;
}

.request-actions button {
    flex: 1;
    min-width: 100px;
    border: none;
    border-radius: 8px;
    padding: 11px 18px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s ease, transform 0.2s ease;
}

.request-actions button:hover {
    transform: translateY(-1px);
}

.accept-btn {
    background: #1D3557;
    color: #FFFFFF;
}

.accept-btn:hover {
    background: #14263F;
}

.reject-btn {
    background: #FFFFFF;
    color: #C44747;
    border: 1px solid #C44747 !important;
}

.reject-btn:hover {
    background: #FEF2F2;
}

.empty-requests {
    background: #F8FAFC;
    border: 1px dashed #CBD5E1;
    border-radius: 14px;
    padding: 35px 25px;
    text-align: center;
    color: #6B7280;
}

.empty-requests h3 {
    color: #1D3557;
    font-size: 18px;
    margin: 0 0 8px;
}

.empty-requests p {
    font-size: 14px;
    margin: 0;
    line-height: 1.6;
}

@media (max-width: 600px) {
    .requests-page {
        padding: 25px 0 40px;
    }

    .requests-heading h1 {
        font-size: 26px;
    }

    .request-section h2 {
        font-size: 20px;
    }

    .request-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .request-card {
        padding: 20px;
    }

    .request-actions {
        flex-direction: column;
    }

    .request-actions button {
        width: 100%;
    }
}
</style>

<div class="requests-page">

    <div class="requests-heading">
        <h1>My Requests</h1>

        <p>
            Manage the book requests you receive and track
            the requests you send.
        </p>
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

                        <h3><?= e($row['title']) ?></h3>

                        <p>
                            <strong>Requested by:</strong>
                            <?= e($row['name']) ?>
                        </p>

                        <p>
                            <strong>Request ID:</strong>
                            #<?= (int) $row['requestId'] ?>
                        </p>

                        <span class="request-status <?= request_status_class($row['status']) ?>">
                            <?= e($row['status']) ?>
                        </span>

                        <?php if (
                            strtolower(trim((string) $row['status'])) === 'pending'
                        ): ?>

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

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($_SESSION['csrf_token']) ?>"
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

                        <h3><?= e($row['title']) ?></h3>

                        <p>
                            <strong>Book owner:</strong>
                            <?= e($row['ownerName']) ?>
                        </p>

                        <p>
                            <strong>Request ID:</strong>
                            #<?= (int) $row['requestId'] ?>
                        </p>

                        <span class="request-status <?= request_status_class($row['status']) ?>">
                            <?= e($row['status']) ?>
                        </span>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
