<?php
// Shared page header (top bar + navigation).
$base      = $base ?? '../';
$pageTitle = $pageTitle ?? 'OBEDS';
$active    = $active ?? '';
$fullWidth = $fullWidth ?? false; // index.php me true set karo (full width hero ke liye)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - OBEDS</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <script>
        try { var t = localStorage.getItem('obeds-theme'); if (t) document.documentElement.dataset.theme = t; } catch (e) {}
    </script>
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

            <button type="button" id="mode" aria-label="Toggle dark mode">🌙</button>

        </nav>
    </div>
</header>
<script>
(function () {
    var b = document.getElementById('mode');
    if (!b) return;
    var r = document.documentElement;
    function isDark() {
        var t = r.dataset.theme;
        return t ? t === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    function sync() { b.textContent = isDark() ? '☀️' : '🌙'; }
    b.onclick = function () {
        var n = isDark() ? 'light' : 'dark';
        r.dataset.theme = n;
        try { localStorage.setItem('obeds-theme', n); } catch (e) {}
        sync();
    };
    sync();
})();
</script>
<main class="<?= $fullWidth ? 'full' : 'wrap' ?>">