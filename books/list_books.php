<?php
require __DIR__ . '/../includes/db.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
     WHERE LOWER(TRIM(status)) = 'available'
       AND category IS NOT NULL
       AND category != ''
     ORDER BY category ASC"
);

$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Get available books
$sql = "SELECT * FROM books
        WHERE LOWER(TRIM(status)) = 'available'";

$params = [];

if ($q !== '') {
    $sql .= " AND (title LIKE ? OR author LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($type !== '') {
    $sql .= " AND LOWER(TRIM(listing_type)) = ?";
    $params[] = $type;
}

if ($category !== '' && in_array($category, $categories, true)) {
    $sql .= " AND category = ?";
    $params[] = $category;
} else {
    $category = '';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Messages: [css class, text]
$msg = $_GET['msg'] ?? '';
$messages = [
    'added'           => ['success', 'Your book is listed successfully.'],
    'deleted'         => ['success', 'Book deleted successfully.'],
    'delete_failed'   => ['error',   'Book could not be deleted. Please try again.'],
    'notallowed'      => ['error',   'You can only delete your own books.'],
    'notfound'        => ['error',   'That book no longer exists.'],
    'requested'       => ['success', 'Your request has been sent successfully!'],
    'already_pending' => ['',        'You already have a pending request for this book.'],
    'unavailable'     => ['error',   'This book is not available.'],
    'own_book'        => ['error',   'You cannot request your own book.'],
    'invalid_request' => ['error',   'Invalid request. Please try again.'],
    'invalid_book'    => ['error',   'Invalid request. Please try again.'],
    'invalid_token'   => ['error',   'Security token expired or invalid. Please try again.'],
    'login_required'  => ['error',   'Please login before requesting a book.'],
    'request_failed'  => ['error',   'Your request could not be processed.'],
];

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

// Cover colours for books without an image
$grads = [
    'linear-gradient(135deg,#243074,#4a5bd0)',
    'linear-gradient(135deg,#17803d,#3cc47c)',
    'linear-gradient(135deg,#b23a48,#e56b7a)',
    'linear-gradient(135deg,#c78a0c,#f6c453)',
    'linear-gradient(135deg,#6c5ce7,#a29bfe)',
];

$pageTitle = 'Browse Books';
$active = 'browse';
$base = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1 class="page-title">Browse Books</h1>
        <p>Discover, exchange and donate books with your community.</p>
    </div>
    <a class="btn gold" href="add_book.php">+ List a Book</a>
</div>

<?php if (isset($messages[$msg])): ?>
    <div class="alert <?= $messages[$msg][0] ?>"><?= e($messages[$msg][1]) ?></div>
<?php endif; ?>

<!-- Search + filters -->
<div class="toolbar">

    <form method="get">
        <input
            type="text"
            name="q"
            placeholder="Search by book title or author..."
            value="<?= e($q) ?>"
            aria-label="Search books"
        >

        <?php if ($type !== ''): ?>
            <input type="hidden" name="type" value="<?= e($type) ?>">
        <?php endif; ?>

        <select name="category" aria-label="Filter by category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                    <?= e($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn gold" type="submit">Search</button>
    </form>

    <div class="chips" aria-label="Filter by type">
        <?php foreach (['' => 'All Books', 'exchange' => 'Exchange', 'donate' => 'Donate'] as $v => $label): ?>
            <a
                class="chip <?= $type === $v ? 'active' : '' ?>"
                href="<?= e(chip_link($v, $q, $category)) ?>"
                <?= $type === $v ? 'aria-current="true"' : '' ?>
            >
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

</div>

<div class="results-head">
    <h2>Available Books</h2>
    <span><?= count($books) ?> book(s) found</span>
</div>

<?php if (!$books): ?>
    <div class="empty">
        <h3>No books found</h3>
        <p>Try another search, or <a href="add_book.php">list the first one</a>.</p>
    </div>
<?php endif; ?>

<!-- Book cards -->
<div class="books wide">

    <?php foreach ($books as $b): ?>

        <?php
            $bookType = strtolower(trim($b['listing_type'] ?? 'exchange'));

            $isBookOwner = current_user_id() !== null
                && (int)$b['ownerId'] === (int)current_user_id();

            $grad = $grads[((int)$b['bookId']) % count($grads)];
        ?>

        <article class="book">

            <div class="cover tall" style="background:<?= $grad ?>">
                <?php if (!empty($b['cover_image'])): ?>
                    <img
                        src="../assets/uploads/books/<?= e($b['cover_image']) ?>"
                        alt="Cover of <?= e($b['title']) ?>"
                    >
                <?php else: ?>
                    <span aria-hidden="true">
                        <?= e(mb_strtoupper(mb_substr($b['title'] ?? '?', 0, 1))) ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="bd">

                <h3>
                    <a href="book_details.php?id=<?= (int)$b['bookId'] ?>">
                        <?= e($b['title']) ?>
                    </a>
                </h3>

                <p class="author">By <?= e($b['author'] ?: 'Unknown Author') ?></p>

                <p class="meta">
                    <?= e($b['category'] ?: 'Uncategorized') ?>
                    &bull;
                    <?= e(ucfirst($b['book_condition'] ?: 'Not specified')) ?> condition
                </p>

                <?php if (!empty($b['isbn'])): ?>
                    <p class="meta">ISBN: <?= e($b['isbn']) ?></p>
                <?php endif; ?>

                <?php if (!empty($b['description'])): ?>
                    <p class="desc"><?= e($b['description']) ?></p>
                <?php endif; ?>

                <div class="actions">

                    <div class="row">
                        <span class="badge <?= $bookType === 'donate' ? 'free' : 'exch' ?>">
                            <?= $bookType === 'donate' ? 'Free' : 'For Exchange' ?>
                        </span>

                        <small>
                            <?php if (!empty($b['created_at'])): ?>
                                <?= e(date('d M Y', strtotime($b['created_at']))) ?>
                            <?php else: ?>
                                Date not available
                            <?php endif; ?>
                        </small>
                    </div>

                    <!-- Delete book -->
                    <?php if (can_delete_book($b['ownerId'])): ?>
                        <form
                            method="post"
                            action="delete_book.php"
                            onsubmit="return confirm('Are you sure you want to delete this book?');"
                        >
                            <input type="hidden" name="book_id" value="<?= (int)$b['bookId'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                            <button class="btn danger block" type="submit">Delete Book</button>
                        </form>
                    <?php endif; ?>

                    <!-- Request book -->
                    <?php if ($isBookOwner): ?>

                        <div class="alert">Your listed book</div>

                    <?php elseif (current_user_id() === null): ?>

                        <a class="btn gold block" href="../frontend/login.html">Login to Request</a>

                    <?php else: ?>

                        <form method="POST" action="../backend/request_book.php">
                            <input type="hidden" name="bookId" value="<?= (int)$b['bookId'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                            <button class="btn gold block" type="submit">Request This Book</button>
                        </form>

                    <?php endif; ?>

                </div>

            </div>

        </article>

    <?php endforeach; ?>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>