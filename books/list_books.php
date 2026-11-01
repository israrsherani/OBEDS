<?php
require __DIR__ . '/../includes/db.php';

// Read the search text and the type filter from the address bar
$q    = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
if (!in_array($type, ['exchange', 'donate'], true)) {
    $type = '';
}

// Build the query: only books that are still available
$sql = "SELECT * FROM books WHERE status = 'Available'";
$params = [];

if ($q !== '') {
    $sql .= ' AND (title LIKE ? OR author LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($type !== '') {
    $sql .= ' AND listing_type = ?';
    $params[] = $type;
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Message shown after adding or deleting a book
$msg = $_GET['msg'] ?? '';

// Builds a link for a filter chip, keeping the search text
function chip_link($type, $q) {
    $args = [];
    if ($q !== '')    { $args['q'] = $q; }
    if ($type !== '') { $args['type'] = $type; }
    return 'list_books.php' . ($args ? '?' . http_build_query($args) : '');
}

$pageTitle = 'Browse books';
$active    = 'browse';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1>Browse books </h1>
    <a class="btn" href="add_book.php">List a book</a>
</div>

<?php if ($msg === 'added'): ?>
    <p class="notice ok">Your book is listed.</p>
<?php elseif ($msg === 'deleted'): ?>
    <p class="notice ok">Book deleted.</p>
<?php elseif ($msg === 'notallowed'): ?>
    <p class="notice err">You can only delete your own books.</p>
<?php elseif ($msg === 'notfound'): ?>
    <p class="notice err">That book no longer exists.</p>
<?php endif; ?>

<form class="search" method="get">
    <input type="text" name="q" placeholder="Search by title or author" value="<?= e($q) ?>" aria-label="Search books">
    <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
    <button class="btn" type="submit">Search</button>
</form>

<div class="chips" aria-label="Filter by type">
    <?php foreach (['' => 'All', 'exchange' => 'Exchange', 'donate' => 'Donate'] as $v => $label): ?>
        <a class="chip" href="<?= e(chip_link($v, $q)) ?>" <?= $type === $v ? 'aria-current="true"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php if (!$books): ?>
    <p class="empty">No books found. Try another search, or <a href="add_book.php">list the first one</a>.</p>
<?php endif; ?>

<div class="shelf">
<?php foreach ($books as $b): ?>
    <article class="book t-<?= e($b['listing_type']) ?>">
        <div class="cover">
            <?php if ($b['cover_image']): ?>
                <img src="../assets/uploads/books/<?= e($b['cover_image']) ?>" alt="Cover of <?= e($b['title']) ?>">
            <?php else: ?>
                <!-- No photo uploaded: show the first letter of the title -->
                <span class="no-cover" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($b['title'], 0, 1))) ?></span>
            <?php endif; ?>
        </div>
        <div class="info">
            <h3><?= e($b['title']) ?></h3>
            <p class="by">by <?= e($b['author']) ?></p>
            <p class="meta"><?= e($b['category']) ?> &bull; <?= e(ucfirst($b['book_condition'])) ?> condition</p>
            <?php if ($b['isbn']): ?>
                <p class="meta">ISBN <?= e($b['isbn']) ?></p>
            <?php endif; ?>
            <?php if ($b['description']): ?>
                <p class="desc"><?= nl2br(e($b['description'])) ?></p>
            <?php endif; ?>

            <div class="foot">
                <span class="badge">
                    <?php if ($b['listing_type'] === 'donate'): ?>
                        Free
                    <?php else: ?>
                        For exchange
                    <?php endif; ?>
                </span>
                <small>Listed <?= e(date('d M Y', strtotime($b['created_at']))) ?></small>
            </div>

            <!-- Delete button: shown only to the book's owner or an admin -->
            <?php if (true): ?>
                <form method="post" action="delete_book.php" onsubmit="return confirm('Delete this book?');" style="margin-top:10px;">
                    <input type="hidden" name="book_id" value="<?= (int) $b['bookId'] ?>">
                    <button class="btn danger" type="submit">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>