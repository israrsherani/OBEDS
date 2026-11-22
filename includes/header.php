
<?php
// Shared page header (top bar + navigation).
$base      = $base ?? '../';
$pageTitle = $pageTitle ?? 'OBEDS';
$active    = $active ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - OBEDS</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="wrap bar">

        <a class="brand" href="<?= $base ?>index.php">
            OBEDS
            <small>Online Book Exchange &amp; Donation</small>
        </a>

        <nav aria-label="Main">

            <a href="<?= $base ?>index.php"
               <?= $active === 'home' ? 'aria-current="page"' : '' ?>>
                Home
            </a>

            <a href="<?= $base ?>books/list_books.php"
               <?= $active === 'browse' ? 'aria-current="page"' : '' ?>>
                Browse books
            </a>

            <a class="nav-cta" href="<?= $base ?>books/add_book.php"
               <?= $active === 'add' ? 'aria-current="page"' : '' ?>>
                List a book
            </a>

            <?php if (current_user_id() !== null): ?>

                <a href="<?= $base ?>backend/requests.php"
   <?= $active === 'requests' ? 'aria-current="page"' : '' ?>>
    My Requests
</a>

                <?php if (is_admin()): ?>
                    <a href="<?= $base ?>backend/admin/dashboard.php">
                        Admin Dashboard
                    </a>
                <?php endif; ?>

                <a href="<?= $base ?>backend/logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="<?= $base ?>frontend/login.html">
                    Login
                </a>

                <a href="<?= $base ?>frontend/register.html">
                    Register
                </a>

            <?php endif; ?>

        </nav>
    </div>
</header>
<main class="wrap">
