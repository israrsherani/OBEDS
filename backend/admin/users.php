
<?php
require_once __DIR__ . '/../../includes/db.php';

// Only admins can access this page
if (!is_admin()) {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle suspend / unsuspend action
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST['csrf_token'] ?? '';
    $targetId = filter_input(INPUT_POST, 'userId', FILTER_VALIDATE_INT);

    // Verify CSRF token
    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit("Invalid request.");
    }

    // Validate target user ID
    if (!$targetId || $targetId <= 0) {
        header("Location: users.php?status=invalid");
        exit;
    }

    // Prevent admin from suspending their own account
    if ($targetId === (int) current_user_id()) {
        header("Location: users.php?status=self");
        exit;
    }

    // Get target user's current status
    $stmt = $pdo->prepare(
        "SELECT role, isSuspended
         FROM users
         WHERE userId = ?"
    );

    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    // Only normal user accounts can be suspended
    if (!$target || $target['role'] !== 'user') {
        header("Location: users.php?status=invalid");
        exit;
    }

    // Toggle account status
    $newStatus = (int) $target['isSuspended'] === 1 ? 0 : 1;

    $stmt = $pdo->prepare(
        "UPDATE users
         SET isSuspended = ?
         WHERE userId = ?
         AND role = 'user'"
    );

    $stmt->execute([$newStatus, $targetId]);

    header("Location: users.php?status=updated");
    exit;
}

// Fetch all users
$stmt = $pdo->query(
    "SELECT userId, name, email, role, isSuspended
     FROM users
     ORDER BY userId DESC"
);

$users = $stmt->fetchAll();

// Shared header settings
$base = '../../';
$pageTitle = 'Manage Users';
$active = '';

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
body {
    background: #F8FAFC;
}

.admin-users-page {
    padding: 40px 0 60px;
}

.page-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.page-head h1 {
    color: #1D3557;
    font-size: 32px;
    margin: 0;
}

.back-btn {
    display: inline-block;
    background: #FFFFFF;
    border: 1px solid #1D3557;
    color: #1D3557;
    text-decoration: none;
    border-radius: 8px;
    padding: 10px 17px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.back-btn:hover {
    background: #EFF6FF;
}

.users-summary {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(29, 53, 87, 0.04);
}

.users-summary strong {
    color: #1D3557;
    font-size: 22px;
}

.users-summary span {
    color: #6B7280;
    margin-left: 8px;
}

.notice {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 22px;
    font-size: 14px;
    font-weight: 600;
}

.notice.ok {
    background: #DCFCE7;
    color: #166534;
    border: 1px solid #BBF7D0;
}

.notice.err {
    background: #FEE2E2;
    color: #991B1B;
    border: 1px solid #FECACA;
}

.users-table-container {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    overflow-x: auto;
    box-shadow: 0 4px 14px rgba(29, 53, 87, 0.05);
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

.users-table th {
    background: #1D3557;
    color: #FFFFFF;
    text-align: left;
    padding: 16px;
    font-size: 14px;
    font-weight: 600;
}

.users-table td {
    padding: 16px;
    border-bottom: 1px solid #E2E8F0;
    color: #4B5563;
    font-size: 14px;
    vertical-align: middle;
}

.users-table tbody tr:last-child td {
    border-bottom: none;
}

.users-table tbody tr:hover {
    background: #F8FAFC;
}

.user-active,
.user-suspended {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.user-active {
    background: #DCFCE7;
    color: #166534;
}

.user-suspended {
    background: #FEE2E2;
    color: #991B1B;
}

.admin-label {
    color: #1D3557;
    background: #EFF6FF;
    border: 1px solid #DBEAFE;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.user-action-form {
    margin: 0;
}

.user-action-form button {
    border: none;
    border-radius: 8px;
    padding: 9px 15px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease;
}

.suspend-btn {
    background: #FEE2E2;
    color: #991B1B;
}

.suspend-btn:hover {
    background: #FECACA;
}

.restore-btn {
    background: #DCFCE7;
    color: #166534;
}

.restore-btn:hover {
    background: #BBF7D0;
}

.empty-users {
    background: #FFFFFF;
    border: 1px dashed #CBD5E1;
    border-radius: 14px;
    padding: 35px;
    text-align: center;
    color: #6B7280;
}

@media (max-width: 600px) {
    .admin-users-page {
        padding: 25px 0 40px;
    }

    .page-head h1 {
        font-size: 26px;
    }

    .page-head {
        align-items: flex-start;
    }

    .users-summary {
        padding: 18px;
    }
}
</style>

<div class="admin-users-page">

    <div class="page-head">

        <h1>Manage Users</h1>

        <a class="back-btn" href="dashboard.php">
            &larr; Back to Dashboard
        </a>

    </div>

    <div class="users-summary">
        <strong><?= count($users) ?></strong>
        <span>Registered Users</span>
    </div>

    <!-- Status Messages -->
    <?php if (isset($_GET['status'])): ?>

        <?php if ($_GET['status'] === 'updated'): ?>

            <div class="notice ok">
                User account status updated successfully.
            </div>

        <?php elseif ($_GET['status'] === 'self'): ?>

            <div class="notice err">
                You cannot suspend your own account.
            </div>

        <?php elseif ($_GET['status'] === 'invalid'): ?>

            <div class="notice err">
                Invalid user or admin account.
            </div>

        <?php endif; ?>

    <?php endif; ?>

    <!-- Users Table -->
    <?php if (empty($users)): ?>

        <div class="empty-users">
            No users found.
        </div>

    <?php else: ?>

        <div class="users-table-container">

            <table class="users-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Account Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($users as $user): ?>

                        <tr>

                            <td><?= e($user['userId']) ?></td>

                            <td><?= e($user['name']) ?></td>

                            <td><?= e($user['email']) ?></td>

                            <td><?= e($user['role']) ?></td>

                            <td>

                                <?php if ((int) $user['isSuspended'] === 1): ?>

                                    <span class="user-suspended">
                                        Suspended
                                    </span>

                                <?php else: ?>

                                    <span class="user-active">
                                        Active
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($user['role'] === 'user'): ?>

                                    <form
                                        method="POST"
                                        action="users.php"
                                        class="user-action-form"
                                        onsubmit="return confirm('Are you sure you want to change this account status?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="userId"
                                            value="<?= e($user['userId']) ?>"
                                        >

                                        <?php if ((int) $user['isSuspended'] === 1): ?>

                                            <button
                                                type="submit"
                                                class="restore-btn"
                                            >
                                                Unsuspend
                                            </button>

                                        <?php else: ?>

                                            <button
                                                type="submit"
                                                class="suspend-btn"
                                            >
                                                Suspend
                                            </button>

                                        <?php endif; ?>

                                    </form>

                                <?php else: ?>

                                    <span class="admin-label">
                                        Admin account
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
