
<?php
require_once "../../includes/db.php";

// Admin access protection
if (!is_admin()) {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle suspend / unsuspend
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST['csrf_token'] ?? '';
    $targetId = (int)($_POST['userId'] ?? 0);

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        exit("Invalid request.");
    }

    if ($targetId === (int)current_user_id()) {
        header("Location: users.php?status=self");
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT role, isSuspended FROM users WHERE userId = ?"
    );
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!$target || $target['role'] !== 'user') {
        header("Location: users.php?status=invalid");
        exit;
    }

    $newStatus = (int)$target['isSuspended'] === 1 ? 0 : 1;

    $stmt = $pdo->prepare(
        "UPDATE users SET isSuspended = ? WHERE userId = ? AND role = 'user'"
    );
    $stmt->execute([$newStatus, $targetId]);

    header("Location: users.php?status=updated");
    exit;
}

// Fetch users
$stmt = $pdo->query(
    "SELECT userId, name, email, role, isSuspended
     FROM users
     ORDER BY userId DESC"
);

$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - OBEDS</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>

<main class="wrap">

    <div class="page-head">
        <h1>Manage Users</h1>
        <a class="btn ghost" href="dashboard.php">
            Back to Dashboard
        </a>
    </div>

    <p class="admin-users-count">
        Total Users: <?= count($users) ?>
    </p>

    <?php if (isset($_GET['status'])): ?>

        <?php if ($_GET['status'] === 'updated'): ?>
            <p class="notice ok">User account status updated successfully.</p>
        <?php elseif ($_GET['status'] === 'self'): ?>
            <p class="notice err">You cannot suspend your own account.</p>
        <?php elseif ($_GET['status'] === 'invalid'): ?>
            <p class="notice err">Invalid user or admin account.</p>
        <?php endif; ?>

    <?php endif; ?>

    <?php if (empty($users)): ?>

        <div class="empty">No users found.</div>

    <?php else: ?>

        <div style="overflow-x:auto;">

            <table>
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
                                <?php if ((int)$user['isSuspended'] === 1): ?>
                                    <span class="user-suspended">Suspended</span>
                                <?php else: ?>
                                    <span class="user-active">Active</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($user['role'] === 'user'): ?>

                                    <form method="POST" class="user-action-form"
                                          onsubmit="return confirm('Are you sure you want to change this account status?');">

                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION['csrf_token']) ?>">

                                        <input type="hidden" name="userId"
                                               value="<?= e($user['userId']) ?>">

                                        <?php if ((int)$user['isSuspended'] === 1): ?>
                                            <button type="submit" class="btn user-restore">
                                                Unsuspend
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn danger">
                                                Suspend
                                            </button>
                                        <?php endif; ?>

                                    </form>

                                <?php else: ?>
                                    <span class="admin-label">Admin account</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
