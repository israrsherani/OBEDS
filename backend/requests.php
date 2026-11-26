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

// My own saved phone number
$stmt = $conn->prepare("SELECT phone FROM users WHERE userId = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$meRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$myPhone = trim((string) ($meRow['phone'] ?? ''));

// Fetch Incoming Requests (requester ka email/phone bhi, sirf accept hone par dikhega)
$stmt = $conn->prepare(
    "SELECT
        requests.requestId,
        requests.status,
        books.title,
        users.name,
        users.email AS requesterEmail,
        users.phone AS requesterPhone
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

// Fetch My Sent Requests (owner ka email/phone bhi, sirf accept hone par dikhega)
$stmt = $conn->prepare(
    "SELECT
        requests.requestId,
        requests.status,
        books.title,
        users.name AS ownerName,
        users.email AS ownerEmail,
        users.phone AS ownerPhone
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

// Messages: [css class, text]
$msg = $_GET['msg'] ?? '';
$messages = [
    'phone_saved'   => ['success', 'Your phone number has been saved.'],
    'phone_invalid' => ['error',   'Invalid phone number. Use 7 to 15 digits (a leading + is allowed).'],
    'invalid_token' => ['error',   'Security token expired or invalid. Please try again.'],
    'phone_failed'  => ['error',   'Phone number could not be saved. Please try again.'],
];

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

// Pakistani number ko WhatsApp format mein badalna (0300... -> 92300...)
function wa_number($phone) {
    $digits = preg_replace('/\D+/', '', (string) $phone);

    if ($digits === '') {
        return '';
    }

    if (strpos($digits, '0') === 0) {
        $digits = '92' . substr($digits, 1);
    }

    return $digits;
}

// Contact box: sirf accepted request par call hota hai
function render_contact_box($label, $name, $email, $phone) {
    $wa = wa_number($phone);
    ?>
    <div class="contact-box">
        <p class="contact-title"><?= e($label) ?></p>

        <p><strong>Name:</strong> <?= e($name) ?></p>

        <p>
            <strong>Phone:</strong>
            <?php if (!empty($phone)): ?>
                <a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a>
            <?php else: ?>
                <span class="muted">Not provided</span>
            <?php endif; ?>
        </p>

        <?php if (!empty($email)): ?>
            <p><strong>Email:</strong> <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
        <?php endif; ?>

        <?php if ($wa !== ''): ?>
            <a class="btn gold block" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">
                Chat on WhatsApp
            </a>
        <?php endif; ?>
    </div>
    <?php
}
?>

<div class="page-head">
    <div>
        <h1 class="page-title">My Requests</h1>
        <p>Manage the book requests you receive and track the requests you send.</p>
    </div>
</div>

<?php if (isset($messages[$msg])): ?>
    <div class="alert <?= $messages[$msg][0] ?>"><?= e($messages[$msg][1]) ?></div>
<?php endif; ?>

<!-- My contact number -->
<div class="phone-card">
    <div>
        <h3>Your contact number</h3>
        <p>
            Shared only with the other person after a request is accepted.
            <?php if ($myPhone === ''): ?>
                <strong>You have not added a number yet.</strong>
            <?php endif; ?>
        </p>
    </div>

    <form method="POST" action="save_phone.php">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

        <input
            type="tel"
            name="phone"
            placeholder="e.g. 0300 1234567"
            value="<?= e($myPhone) ?>"
            maxlength="20"
            aria-label="Your phone number"
        >

        <button class="btn gold" type="submit">Save</button>
    </form>
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

                <?php $st = strtolower(trim((string) $row['status'])); ?>

                <article class="req-card">

                    <h3><?= e($row['title']) ?></h3>

                    <p><strong>Requested by:</strong> <?= e($row['name']) ?></p>

                    <p><strong>Request ID:</strong> #<?= (int) $row['requestId'] ?></p>

                    <span class="status <?= request_status_class($row['status']) ?>">
                        <?= e($row['status']) ?>
                    </span>

                    <?php if ($st === 'pending'): ?>

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

                        <p class="contact-note">
                            Contact details are shared after you accept this request.
                        </p>

                    <?php elseif ($st === 'accepted'): ?>

                        <?php render_contact_box(
                            'Requester contact',
                            $row['name'],
                            $row['requesterEmail'] ?? '',
                            $row['requesterPhone'] ?? ''
                        ); ?>

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

                <?php $st = strtolower(trim((string) $row['status'])); ?>

                <article class="req-card">

                    <h3><?= e($row['title']) ?></h3>

                    <p><strong>Book owner:</strong> <?= e($row['ownerName']) ?></p>

                    <p><strong>Request ID:</strong> #<?= (int) $row['requestId'] ?></p>

                    <span class="status <?= request_status_class($row['status']) ?>">
                        <?= e($row['status']) ?>
                    </span>

                    <?php if ($st === 'accepted'): ?>

                        <?php render_contact_box(
                            'Owner contact',
                            $row['ownerName'],
                            $row['ownerEmail'] ?? '',
                            $row['ownerPhone'] ?? ''
                        ); ?>

                    <?php elseif ($st === 'pending'): ?>

                        <p class="contact-note">
                            Owner's contact details will appear here once your request is accepted.
                        </p>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>