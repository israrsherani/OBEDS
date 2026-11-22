
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

<style>
body {
    background: #F8FAFC;
}

.admin-dashboard {
    padding: 40px 0 60px;
}

.admin-heading {
    margin-bottom: 35px;
}

.admin-heading h1 {
    color: #1D3557;
    font-size: 32px;
    margin: 0 0 10px;
}

.admin-heading p {
    color: #6B7280;
    margin: 0;
    line-height: 1.7;
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 22px;
    margin-bottom: 40px;
}

.stat-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 26px;
    box-shadow: 0 4px 14px rgba(29, 53, 87, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(29, 53, 87, 0.09);
}

.stat-card h2 {
    font-size: 34px;
    color: #1D3557;
    margin: 0 0 10px;
}

.stat-card p {
    color: #6B7280;
    font-size: 15px;
    font-weight: 600;
    margin: 0;
}

.admin-section-title {
    color: #1D3557;
    font-size: 23px;
    margin-bottom: 20px;
}

.admin-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 360px));
    gap: 22px;
}

.admin-action-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 25px;
    box-shadow: 0 4px 14px rgba(29, 53, 87, 0.05);
}

.admin-action-card h3 {
    color: #1D3557;
    font-size: 19px;
    margin: 0 0 10px;
}

.admin-action-card p {
    color: #6B7280;
    font-size: 14px;
    line-height: 1.7;
    margin: 0 0 20px;
}

.admin-action-card a {
    display: inline-block;
    background: #1D3557;
    color: #FFFFFF;
    text-decoration: none;
    border-radius: 8px;
    padding: 11px 18px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.admin-action-card a:hover {
    background: #14263F;
}

@media (max-width: 600px) {
    .admin-dashboard {
        padding: 25px 0 40px;
    }

    .admin-heading h1 {
        font-size: 26px;
    }

    .admin-stats {
        grid-template-columns: 1fr;
    }

    .admin-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="admin-dashboard">

    <div class="admin-heading">
        <h1>Admin Dashboard</h1>

        <p>
            Welcome, <?= e($_SESSION['name'] ?? 'Admin') ?>!
            Manage users, book listings and monitor OBEDS activity.
        </p>
    </div>

    <!-- Dashboard Statistics -->
    <section class="admin-stats">

        <article class="stat-card">
            <h2><?= $totalUsers ?></h2>
            <p>Total Users</p>
        </article>

        <article class="stat-card">
            <h2><?= $totalBooks ?></h2>
            <p>Total Books</p>
        </article>

        <article class="stat-card">
            <h2><?= $availableBooks ?></h2>
            <p>Available Books</p>
        </article>

        <article class="stat-card">
            <h2><?= $pendingRequests ?></h2>
            <p>Pending Requests</p>
        </article>

    </section>

    <!-- Admin Management -->
    <section>

        <h2 class="admin-section-title">Administration</h2>

        <div class="admin-actions">

            <article class="admin-action-card">
                <h3>Manage Users</h3>
                <p>
                    View registered users and manage user accounts
                    and account status.
                </p>
                <a href="users.php">Manage Users</a>
            </article>

            <article class="admin-action-card">
                <h3>Manage Books</h3>
                <p>
                    Review book listings and manage books
                    published on the platform.
                </p>
                <a href="books.php">Manage Books</a>
            </article>

        </div>

    </section>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
