<?php
require_once __DIR__ . '/../includes/db.php';

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$type = trim($_GET['type'] ?? '');

$sql = "SELECT bookId, title, author, category, book_condition, listing_type FROM books WHERE status = 'Available'";
$params = [];

if ($q !== '') {
    $sql .= " AND (title LIKE :q1 OR author LIKE :q2)";
    $params[':q1'] = "%$q%";
    $params[':q2'] = "%$q%";
}
if ($category !== '') {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}
if ($type !== '') {
    $sql .= " AND listing_type = :type";
    $params[':type'] = $type;
}
$sql .= " ORDER BY title";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Search Books - OBEDS</title>
</head>
<body>
    <h1>Search Books</h1>

    <form method="GET" action="search_books.php">
        <label for="q">Title or Author</label>
        <input type="text" id="q" name="q" value="<?= e($q) ?>">

        <label for="category">Category</label>
        <select id="category" name="category">
            <option value="">All</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="type">Type</label>
        <select id="type" name="type">
            <option value="">All</option>
            <option value="exchange" <?= $type === 'exchange' ? 'selected' : '' ?>>Exchange</option>
            <option value="donate" <?= $type === 'donate' ? 'selected' : '' ?>>Donate</option>
        </select>

        <button type="submit">Search</button>
    </form>

    <?php if (empty($books)): ?>
        <p>No books found.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Condition</th>
                <th>Type</th>
            </tr>
            <?php foreach ($books as $b): ?>
                <tr>
                    <td><?= e($b['title']) ?></td>
                    <td><?= e($b['author']) ?></td>
                    <td><?= e($b['category']) ?></td>
                    <td><?= e($b['book_condition']) ?></td>
                    <td><?= e($b['listing_type']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>