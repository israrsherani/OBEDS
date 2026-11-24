<?php
require_once __DIR__ . '/../../includes/db.php';

// Only admins can access this page
if (!is_admin()) {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

// Dashboard statistics
$totalUsers = $pdo->query(
    "SELECT COUNT(*) FROM users"
)->fetchColumn();

$totalBooks = $pdo->query(
    "SELECT COUNT(*) FROM books"
)->fetchColumn();

$availableBooks = $pdo->query(
    "SELECT COUNT(*) FROM books WHERE status = 'Available'"
)->fetchColumn();

$pendingRequests = $pdo->query(
    "SELECT COUNT(*) FROM requests WHERE status = 'Pending'"
)->fetchColumn();

// Shared header settings
$base = '../../';
$pageTitle = 'Admin Dashboard';
$active = '';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p>
            Welcome, <?= e($_SESSION['name'] ?? 'Admin') ?>!
            Manage users, book listings and monitor OBEDS activity.
        </p>
    </div>
</div>

<!-- Dashboard Statistics -->
<section class="stat-grid four">

    <article class="stat">
        <div class="ico" aria-hidden="true">👥</div>
        <div>
            <b><?= (int) $totalUsers ?></b>
            <span>Total Users</span>
        </div>
    </article>

    <article class="stat">
        <div class="ico" aria-hidden="true">📚</div>
        <div>
            <b><?= (int) $totalBooks ?></b>
            <span>Total Books</span>
        </div>
    </article>

    <article class="stat">
        <div class="ico" aria-hidden="true">✅</div>
        <div>
            <b><?= (int) $availableBooks ?></b>
            <span>Available Books</span>
        </div>
    </article>

    <article class="stat">
        <div class="ico" aria-hidden="true">⏳</div>
        <div>
            <b><?= (int) $pendingRequests ?></b>
            <span>Pending Requests</span>
        </div>
    </article>

</section>

<!-- Admin Management -->
<section>

    <h2 class="section-title">Administration</h2>

    <div class="admin-grid">

        <article class="action-card">
            <div class="ico" aria-hidden="true">👤</div>
            <h3>Manage Users</h3>
            <p>
                View registered users and manage user accounts
                and account status.
            </p>
            <a class="btn gold" href="users.php">Manage Users</a>
        </article>

        <article class="action-card">
            <div class="ico" aria-hidden="true">📖</div>
            <h3>Manage Books</h3>
            <p>
                Review book listings and manage books
                published on the platform.
            </p>
            <a class="btn gold" href="books.php">Manage Books</a>
        </article>

    </div>

</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>