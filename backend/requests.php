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
// (the requester's email/phone are only returned once the request is accepted)
$stmt = $conn->prepare(
    "SELECT
        requests.requestId,
        requests.status,
        books.title,
        users.name,
        CASE WHEN LOWER(requests.status) = 'accepted' THEN users.email END AS contactEmail,
        CASE WHEN LOWER(requests.status) = 'accepted' THEN users.phone END AS contactPhone
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
// (the owner's email/phone are only returned once the request is accepted)
$stmt = $conn->prepare(
    "SELECT
        requests.requestId,
        requests.status,
        books.title,
        users.name AS ownerName,
        CASE WHEN LOWER(requests.status) = 'accepted' THEN users.email END AS contactEmail,
        CASE WHEN LOWER(requests.status) = 'accepted' THEN users.phone END AS contactPhone
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

// WhatsApp link from a phone number (Pakistan: 03xx... becomes 923xx...)
function whatsapp_link($phone) {
    $digits = preg_replace('/\D/', '', (string) $phone);
    if ($digits === '') {
        return '';
    }
    if (strpos($digits, '00') === 0) {
        $digits = substr($digits, 2);
    } elseif ($digits[0] === '0') {
        $digits = '92' . substr($digits, 1);
    }
    return 'https://wa.me/' . $digits;
}

// Contact box shown on accepted requests
function render_contact_box($email, $phone, $label) {
    ?>
    <div class="contact-box">
        <strong><?= e($label) ?></strong>
        <p>Email: <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
        <?php if (!empty($phone)): ?>
            <p>Phone: <a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a></p>
            <a class="btn gold block" href="<?= e(whatsapp_link($phone)) ?>" target="_blank" rel="noopener">
                Chat on WhatsApp
            </a>
        <?php else: ?>
            <p>Phone number not provided.</p>
        <?php endif; ?>
    </div>
    <?php
}
?>

<style>
.contact-box{margin-top:16px;padding:14px 16px;border-radius:12px;background:rgba(224,165,38,.12);border:1px solid var(--gold)}
.contact-box strong{display:block;margin-bottom:4px}
.contact-box p{margin:4px 0;overflow-wrap:anywhere}
.contact-box a:not(.btn){color:var(--navy-2);font-weight:600;text-decoration:underline}
:root[data-theme="dark"] .contact-box a:not(.btn){color:var(--gold-2)}
@media (prefers-color-scheme:dark){:root:not([data-theme]) .contact-box a:not(.btn){color:var(--gold-2)}}
</style>

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

                    <?php elseif (strtolower(trim((string) $row['status'])) === 'accepted'): ?>

                        <?php render_contact_box($row['contactEmail'], $row['contactPhone'], 'Contact the requester to arrange the handover'); ?>

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

                    <?php if (strtolower(trim((string) $row['status'])) === 'accepted'): ?>

                        <?php render_contact_box($row['contactEmail'], $row['contactPhone'], 'Request accepted. Contact the owner to get your book'); ?>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>