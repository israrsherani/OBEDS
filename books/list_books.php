
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

$pageTitle = 'Browse Books';
$active = 'browse';
$base = '../';

require __DIR__ . '/../includes/header.php';
?>

<style>
.browse-page {
    max-width: 1200px;
    margin: 35px auto;
    padding: 0 20px;
}

.browse-page * {
    box-sizing: border-box;
}

.browse-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.browse-heading h1 {
    color: #1D3557;
    font-size: 32px;
    margin: 0 0 8px;
}

.browse-heading p {
    color: #64748b;
    margin: 0;
}

.browse-page a {
    text-decoration: none;
}

.browse-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #1D3557;
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 12px 20px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: 0.2s ease;
}

.browse-btn:hover {
    background: #294b72;
    transform: translateY(-1px);
}

.browse-btn.light {
    background: #e0f2fe;
    color: #1D3557;
}

.browse-btn.light:hover {
    background: #cde8f8;
}

.browse-btn.danger {
    background: #dc2626;
    margin-top: 10px;
}

.browse-btn.danger:hover {
    background: #b91c1c;
}

.browse-toolbar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 15px;
    padding: 22px;
    margin-bottom: 22px;
    box-shadow: 0 4px 18px rgba(29, 53, 87, 0.04);
}

.browse-search {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.browse-search input,
.browse-search select,
.browse-category-form select {
    flex: 1;
    min-width: 200px;
    padding: 13px 15px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    background: #fff;
    color: #334155;
    font-size: 15px;
    outline: none;
}

.browse-search input:focus,
.browse-search select:focus,
.browse-category-form select:focus {
    border-color: #38BDF8;
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.12);
}

.browse-type-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.type-chip {
    padding: 9px 19px;
    border-radius: 30px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    font-size: 14px;
    font-weight: 600;
    transition: 0.2s;
}

.type-chip:hover {
    background: #e0f2fe;
    color: #1D3557;
}

.type-chip.active {
    background: #1D3557;
    border-color: #1D3557;
    color: #fff;
}

.browse-category-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.browse-notice {
    padding: 15px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
    background: #f1f5f9;
    color: #334155;
}

.browse-notice.ok {
    background: #ecfdf5;
    border-color: #bbf7d0;
    color: #166534;
}

.browse-notice.err {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}

.browse-results-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin: 28px 0 20px;
}

.browse-results-heading h2 {
    color: #1D3557;
    font-size: 22px;
    margin: 0;
}

.browse-results-heading span {
    color: #64748b;
    font-size: 14px;
}

.browse-shelf {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
}

.browse-book {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 15px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-width: 0;
    box-shadow: 0 5px 20px rgba(29, 53, 87, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.browse-book:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(29, 53, 87, 0.10);
}

.browse-cover {
    height: 220px;
    background: #eaf2f8;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

.browse-cover img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 12px;
}

.browse-no-cover {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 65px;
    font-weight: bold;
    color: #1D3557;
    background: linear-gradient(135deg, #dbeafe, #e0f2fe);
}

.browse-book-info {
    padding: 22px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.browse-book-info h3 {
    font-size: 20px;
    line-height: 1.45;
    margin: 0 0 10px;
    overflow-wrap: anywhere;
}

.browse-book-info h3 a {
    color: #1D3557;
}

.browse-book-info h3 a:hover {
    color: #38BDF8;
}

.browse-author {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 14px;
}

.browse-meta {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 8px;
    overflow-wrap: anywhere;
}

.browse-description {
    font-size: 14px;
    color: #475569;
    line-height: 1.7;
    margin-top: 12px;
    overflow-wrap: anywhere;
}

.browse-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 18px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
}

.browse-badge {
    display: inline-block;
    border-radius: 30px;
    padding: 8px 13px;
    font-size: 12px;
    font-weight: 700;
}

.browse-badge.exchange {
    background: #e0f2fe;
    color: #075985;
}

.browse-badge.donate {
    background: #dcfce7;
    color: #166534;
}

.browse-foot small {
    color: #94a3b8;
    font-size: 12px;
}

.browse-empty {
    text-align: center;
    background: #fff;
    border: 1px dashed #cbd5e1;
    border-radius: 15px;
    padding: 45px 25px;
    color: #64748b;
    margin-bottom: 25px;
}

.browse-empty h3 {
    color: #1D3557;
    margin-bottom: 10px;
}

.browse-empty a {
    color: #38BDF8;
    font-weight: 600;
}

.browse-book-info form {
    margin-top: 10px;
}

.browse-book-info form button {
    width: 100%;
}

@media (max-width: 950px) {
    .browse-shelf {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .browse-page {
        margin: 25px auto;
        padding: 0 15px;
    }

    .browse-heading h1 {
        font-size: 26px;
    }

    .browse-toolbar {
        padding: 17px;
    }

    .browse-search,
    .browse-category-form {
        flex-direction: column;
    }

    .browse-search input,
    .browse-search button,
    .browse-category-form select,
    .browse-category-form button {
        width: 100%;
    }

    .browse-shelf {
        grid-template-columns: 1fr;
    }

    .browse-cover {
        height: 240px;
    }

    .browse-book-info {
        padding: 18px;
    }
}
</style>

<div class="browse-page">

    <div class="browse-heading">
        <div>
            <h1>Browse Books</h1>
            <p>Discover, exchange and donate books with your community.</p>
        </div>

        <a class="browse-btn" href="add_book.php">
            + List a Book
        </a>
    </div>

    <!-- Messages -->

    <?php if ($msg === 'added'): ?>
        <div class="browse-notice ok">Your book is listed successfully.</div>

    <?php elseif ($msg === 'deleted'): ?>
        <div class="browse-notice ok">Book deleted successfully.</div>

    <?php elseif ($msg === 'delete_failed'): ?>
        <div class="browse-notice err">Book could not be deleted. Please try again.</div>

    <?php elseif ($msg === 'notallowed'): ?>
        <div class="browse-notice err">You can only delete your own books.</div>

    <?php elseif ($msg === 'notfound'): ?>
        <div class="browse-notice err">That book no longer exists.</div>

    <?php elseif ($msg === 'requested'): ?>
        <div class="browse-notice ok">Your request has been sent successfully!</div>

    <?php elseif ($msg === 'already_pending'): ?>
        <div class="browse-notice">You already have a pending request for this book.</div>

    <?php elseif ($msg === 'unavailable'): ?>
        <div class="browse-notice err">This book is not available.</div>

    <?php elseif ($msg === 'own_book'): ?>
        <div class="browse-notice err">You cannot request your own book.</div>

    <?php elseif ($msg === 'invalid_request' || $msg === 'invalid_book'): ?>
        <div class="browse-notice err">Invalid request. Please try again.</div>

    <?php elseif ($msg === 'invalid_token'): ?>
        <div class="browse-notice err">Security token expired or invalid. Please try again.</div>

    <?php elseif ($msg === 'login_required'): ?>
        <div class="browse-notice err">Please login before requesting a book.</div>

    <?php elseif ($msg === 'request_failed'): ?>
        <div class="browse-notice err">Your request could not be processed.</div>
    <?php endif; ?>

    <!-- Search -->

    <div class="browse-toolbar">

        <form class="browse-search" method="get">

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

            <?php if ($category !== ''): ?>
                <input type="hidden" name="category" value="<?= e($category) ?>">
            <?php endif; ?>

            <button class="browse-btn" type="submit">
                Search Books
            </button>

        </form>

        <!-- Type filters -->

        <div class="browse-type-filters" aria-label="Filter by type">

            <?php foreach (['' => 'All Books', 'exchange' => 'Exchange', 'donate' => 'Donate'] as $v => $label): ?>

                <a
                    class="type-chip <?= $type === $v ? 'active' : '' ?>"
                    href="<?= e(chip_link($v, $q, $category)) ?>"
                    <?= $type === $v ? 'aria-current="true"' : '' ?>
                >
                    <?= e($label) ?>
                </a>

            <?php endforeach; ?>

        </div>

        <!-- Category filter -->

        <form class="browse-category-form" method="get">

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

            <button class="browse-btn light" type="submit">
                Filter Category
            </button>

        </form>

    </div>

    <!-- Results heading -->

    <div class="browse-results-heading">
        <h2>Available Books</h2>
        <span><?= count($books) ?> book(s) found</span>
    </div>

    <?php if (!$books): ?>

        <div class="browse-empty">
            <h3>No books found</h3>
            <p>
                Try another search, or
                <a href="add_book.php">list the first one</a>.
            </p>
        </div>

    <?php endif; ?>

    <!-- Book cards -->

    <div class="browse-shelf">

        <?php foreach ($books as $b): ?>

            <?php
                $bookType = strtolower(trim($b['listing_type'] ?? 'exchange'));

                $isBookOwner = current_user_id() !== null
                    && (int)$b['ownerId'] === (int)current_user_id();
            ?>

            <article class="browse-book">

                <div class="browse-cover">

                    <?php if (!empty($b['cover_image'])): ?>

                        <img
                            src="../assets/uploads/books/<?= e($b['cover_image']) ?>"
                            alt="Cover of <?= e($b['title']) ?>"
                        >

                    <?php else: ?>

                        <span class="browse-no-cover" aria-hidden="true">
                            <?= e(mb_strtoupper(mb_substr($b['title'] ?? '?', 0, 1))) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <div class="browse-book-info">

                    <h3>
                        <a href="book_details.php?id=<?= (int)$b['bookId'] ?>">
                            <?= e($b['title']) ?>
                        </a>
                    </h3>

                    <p class="browse-author">
                        By <?= e($b['author'] ?: 'Unknown Author') ?>
                    </p>

                    <p class="browse-meta">
                        <?= e($b['category'] ?: 'Uncategorized') ?>
                        &bull;
                        <?= e(ucfirst($b['book_condition'] ?: 'Not specified')) ?> condition
                    </p>

                    <?php if (!empty($b['isbn'])): ?>
                        <p class="browse-meta">
                            ISBN: <?= e($b['isbn']) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($b['description'])): ?>
                        <p class="browse-description">
                            <?= nl2br(e($b['description'])) ?>
                        </p>
                    <?php endif; ?>

                    <div class="browse-foot">

                        <span class="browse-badge <?= $bookType === 'donate' ? 'donate' : 'exchange' ?>">
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

                            <input
                                type="hidden"
                                name="book_id"
                                value="<?= (int)$b['bookId'] ?>"
                            >

                            <!-- CSRF protection -->
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($_SESSION['csrf_token']) ?>"
                            >

                            <button class="browse-btn danger" type="submit">
                                Delete Book
                            </button>

                        </form>

                    <?php endif; ?>

                    <!-- Request book -->

                    <?php if ($isBookOwner): ?>

                        <div class="browse-notice">
                            Your listed book
                        </div>

                    <?php elseif (current_user_id() === null): ?>

                        <a class="browse-btn" href="../frontend/login.html">
                            Login to Request
                        </a>

                    <?php else: ?>

                        <form method="POST" action="../backend/request_book.php">

                            <input
                                type="hidden"
                                name="bookId"
                                value="<?= (int)$b['bookId'] ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($_SESSION['csrf_token']) ?>"
                            >

                            <button class="browse-btn" type="submit">
                                Request This Book
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
