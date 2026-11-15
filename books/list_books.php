
<?php
require __DIR__ . '/../includes/db.php';

// Read search and filters
$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$category = trim($_GET['category'] ?? '');

if (!in_array($type, ['exchange', 'donate'], true)) {
    $type = '';
}

// Get available categories
$categoryStmt = $pdo->query(
    "SELECT DISTINCT category
     FROM books
     WHERE status = 'Available'
     AND category IS NOT NULL
     AND category != ''
     ORDER BY category ASC"
);

$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Get available books
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

if ($category !== '' && in_array($category, $categories, true)) {
    $sql .= ' AND category = ?';
    $params[] = $category;
} else {
    $category = '';
}

$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Messages
$msg = $_GET['msg'] ?? '';

// Build filter links
function chip_link($type, $q, $category) {
    $args = [];

    if ($q !== '') {
        $args['q'] = $q;
    }

    if ($type !== '') {
        $args['type'] = $type;
    }

    if ($category !== '') {
        $args['category'] = $category;
    }

    return 'list_books.php' . ($args ? '?' . http_build_query($args) : '');
}

$pageTitle = 'Browse books';
$active = 'browse';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <h1>Browse books</h1>
    <a class="btn" href="add_book.php">List a book</a>
</div>

<!-- Success and error messages -->

<?php if ($msg === 'added'): ?>
    <p class="notice ok">Your book is listed.</p>

<?php elseif ($msg === 'deleted'): ?>
    <p class="notice ok">Book deleted.</p>

<?php elseif ($msg === 'notallowed'): ?>
    <p class="notice err">You can only delete your own books.</p>

<?php elseif ($msg === 'notfound'): ?>
    <p class="notice err">That book no longer exists.</p>

<?php elseif ($msg === 'requested'): ?>
    <p class="notice ok">
        Request sent successfully! You can track its status in My Sent Requests.
    </p>

<?php elseif ($msg === 'already_pending'): ?>
    <p class="notice">
        You already have a pending request for this book.
    </p>

<?php elseif ($msg === 'unavailable'): ?>
    <p class="notice err">This book is not available.</p>

<?php elseif ($msg === 'own_book'): ?>
    <p class="notice err">You cannot request your own book.</p>

<?php elseif ($msg === 'invalid_request' || $msg === 'invalid_book'): ?>
    <p class="notice err">Invalid request. Please try again.</p>

<?php elseif ($msg === 'login_required'): ?>
    <p class="notice err">Please login before requesting a book.</p>
<?php endif; ?>

<!-- Search -->
<form class="search" method="get">

    <input
        type="text"
        name="q"
        placeholder="Search by title or author"
        value="<?= e($q) ?>"
        aria-label="Search books"
    >

    <?php if ($type !== ''): ?>
        <input type="hidden" name="type" value="<?= e($type) ?>">
    <?php endif; ?>

    <?php if ($category !== ''): ?>
        <input type="hidden" name="category" value="<?= e($category) ?>">
    <?php endif; ?>

    <button class="btn" type="submit">Search</button>
</form>

<!-- Exchange and Donate filters -->
<div class="chips" aria-label="Filter by type">

    <?php foreach (['' => 'All', 'exchange' => 'Exchange', 'donate' => 'Donate'] as $v => $label): ?>

        <a
            class="chip"
            href="<?= e(chip_link($v, $q, $category)) ?>"
            <?= $type === $v ? 'aria-current="true"' : '' ?>
        >
            <?= e($label) ?>
        </a>

    <?php endforeach; ?>

</div>

<!-- Category Filter -->
<form class="search" method="get">

    <?php if ($q !== ''): ?>
        <input type="hidden" name="q" value="<?= e($q) ?>">
    <?php endif; ?>

    <?php if ($type !== ''): ?>
        <input type="hidden" name="type" value="<?= e($type) ?>">
    <?php endif; ?>

    <select name="category" aria-label="Filter by category">

        <option value="">All Categories</option>

        <?php foreach ($categories as $cat): ?>
            <option
                value="<?= e($cat) ?>"
                <?= $category === $cat ? 'selected' : '' ?>
            >
                <?= e($cat) ?>
            </option>
        <?php endforeach; ?>

    </select>

    <button class="btn" type="submit">Filter Category</button>

</form>

<!-- No books message -->
<?php if (!$books): ?>
    <p class="empty">
        No books found. Try another search, or
        <a href="add_book.php">list the first one</a>.
    </p>
<?php endif; ?>

<!-- Display books -->
<div class="shelf">

<?php foreach ($books as $b): ?>

    <article class="book t-<?= e($b['listing_type']) ?>">

        <div class="cover">

            <?php if (!empty($b['cover_image'])): ?>
                <img
                    src="../assets/uploads/books/<?= e($b['cover_image']) ?>"
                    alt="Cover of <?= e($b['title']) ?>"
                >
            <?php else: ?>
                <span class="no-cover" aria-hidden="true">
                    <?= e(mb_strtoupper(mb_substr($b['title'], 0, 1))) ?>
                </span>
            <?php endif; ?>

        </div>

        <div class="info">

            <h3>
                <a href="book_details.php?id=<?= (int)$b['bookId'] ?>">
                    <?= e($b['title']) ?>
                </a>
            </h3>

            <p class="by">
                by <?= e($b['author']) ?>
            </p>

            <p class="meta">
                <?= e($b['category']) ?>
                &bull;
                <?= e(ucfirst($b['book_condition'])) ?> condition
            </p>

            <?php if (!empty($b['isbn'])): ?>
                <p class="meta">
                    ISBN <?= e($b['isbn']) ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($b['description'])): ?>
                <p class="desc">
                    <?= nl2br(e($b['description'])) ?>
                </p>
            <?php endif; ?>

            <div class="foot">

                <span class="badge">
                    <?php if ($b['listing_type'] === 'donate'): ?>
                        Free
                    <?php else: ?>
                        For exchange
                    <?php endif; ?>
                </span>

                <small>
                    Listed <?= e(date('d M Y', strtotime($b['created_at']))) ?>
                </small>

            </div>

            <!-- Delete permission -->
            <?php if (can_delete_book($b['ownerId'])): ?>

                <form
                    method="post"
                    action="delete_book.php"
                    onsubmit="return confirm('Are you sure you want to delete this book?');"
                    style="margin-top:10px;"
                >

                    <input
                        type="hidden"
                        name="book_id"
                        value="<?= (int)$b['bookId'] ?>"
                    >

                    <button class="btn danger" type="submit">
                        Delete
                    </button>

                </form>

            <?php endif; ?>

            <!-- Request Book -->
            <?php if (
                current_user_id() !== null &&
                $b['ownerId'] !== null &&
                (int)$b['ownerId'] !== (int)current_user_id()
            ): ?>

                <form
                    method="POST"
                    action="../backend/request_book.php"
                    style="margin-top:10px;"
                >

                    <input
                        type="hidden"
                        name="bookId"
                        value="<?= (int)$b['bookId'] ?>"
                    >

                    <button class="btn" type="submit">
                        Request this book
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </article>

<?php endforeach; ?>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
