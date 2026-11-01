<?php
require __DIR__ . '/../includes/db.php';

$q    = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';

$sql = "SELECT * FROM books WHERE status = 'available'";
$params = [];

if ($q !== '') {
    $sql .= ' AND (title LIKE ? OR author LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if (in_array($type, ['exchange', 'donate'], true)) {
    $sql .= ' AND listing_type = ?';
    $params[] = $type;
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Available Books - OBEDS</title>
</head>
<body>
    <h1>Available Books</h1>
    <p><a href="add_book.php">+ List a Book</a></p>

    <?php if (($_GET['msg'] ?? '') === 'added'): ?>
        <p style="color:green;">Book listed successfully!</p>
    <?php endif; ?>

    <form method="get">
        <input type="text" name="q" placeholder="Search by title or author" value="<?= e($q) ?>">
        <select name="type">
            <option value="">All</option>
            <option value="exchange" <?= $type === 'exchange' ? 'selected' : '' ?>>Exchange</option>
            <option value="donate" <?= $type === 'donate' ? 'selected' : '' ?>>Donate</option>
        </select>
        <button type="submit">Search</button>
    </form>
    <hr>

    <?php if (!$books): ?>
        <p>No books found.</p>
    <?php endif; ?>

    <?php foreach ($books as $b): ?>
        <div class="book-card">
            <?php if ($b['cover_image']): ?>
                <img src="../assets/uploads/books/<?= e($b['cover_image']) ?>" alt="<?= e($b['title']) ?>" width="120">
            <?php endif; ?>
            <h3><?= e($b['title']) ?></h3>
            <p>by <?= e($b['author']) ?></p>
            <p>Category: <?= e($b['category']) ?> | Condition: <?= e(ucfirst($b['book_condition'])) ?></p>
            <p>Type: <?= $b['listing_type'] === 'donate' ? 'Free (Donate)' : 'Exchange' ?></p>
            <?php if ($b['description']): ?>
                <p><?= nl2br(e($b['description'])) ?></p>
            <?php endif; ?>
            <small>Listed on <?= e(date('d M Y', strtotime($b['created_at']))) ?></small>
        </div>
        <hr>
    <?php endforeach; ?>
</body>
</html>
