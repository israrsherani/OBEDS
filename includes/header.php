<?php
// Shared page header (top bar + navigation).
// Before including, a page may set:
//   $pageTitle  - text for the browser tab
//   $base       - path back to the project root ('' for index.php, '../' for pages in /books)
//   $active     - which menu link to highlight: 'home', 'browse' or 'add'
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
        <a class="brand" href="<?= $base ?>index.php">OBEDS<small>Online Book Exchange &amp; Donation</small></a>
        <nav aria-label="Main">
            <a href="<?= $base ?>index.php" <?= $active === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
            <a href="<?= $base ?>books/list_books.php" <?= $active === 'browse' ? 'aria-current="page"' : '' ?>>Browse books</a>
            <a class="nav-cta" href="<?= $base ?>books/add_book.php" <?= $active === 'add' ? 'aria-current="page"' : '' ?>>List a book</a>
        </nav>
    </div>
</header>
<main class="wrap">
