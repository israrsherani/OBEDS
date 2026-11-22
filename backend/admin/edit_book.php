
<?php
require_once __DIR__ . '/../../includes/db.php';

// Admin access protection
if (!is_admin()) {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get book ID
$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = filter_input(INPUT_POST, 'bookId', FILTER_VALIDATE_INT);
}

if (!$bookId || $bookId <= 0) {
    http_response_code(400);
    exit("Invalid book ID.");
}

// Fetch book
$stmt = $pdo->prepare(
    "SELECT * FROM books WHERE bookId = ?"
);
$stmt->execute([$bookId]);

$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    exit("Book not found.");
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    // Verify CSRF token
    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit("Invalid request.");
    }

    // Collect form data
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $condition = $_POST['book_condition'] ?? '';
    $listingType = $_POST['listing_type'] ?? '';
    $description = trim($_POST['description'] ?? '');

    // Allowed values
    $allowedConditions = ['new', 'good', 'fair', 'poor'];
    $allowedTypes = ['exchange', 'donate'];

    // Validate fields
    if (
        $title === '' ||
        $author === '' ||
        $category === '' ||
        !in_array($condition, $allowedConditions, true) ||
        !in_array($listingType, $allowedTypes, true)
    ) {
        $error = "Please complete all required fields correctly.";
    } elseif (
        strlen($title) > 255 ||
        strlen($author) > 255 ||
        strlen($category) > 100
    ) {
        $error = "One or more fields exceed the allowed length.";
    } else {

        // Update book details
        $stmt = $pdo->prepare(
            "UPDATE books
             SET title = ?,
                 author = ?,
                 category = ?,
                 book_condition = ?,
                 listing_type = ?,
                 description = ?
             WHERE bookId = ?"
        );

        $stmt->execute([
            $title,
            $author,
            $category,
            $condition,
            $listingType,
            $description,
            $bookId
        ]);

        header("Location: books.php?status=updated");
        exit;
    }

    // Preserve submitted values if validation fails
    $book['title'] = $title;
    $book['author'] = $author;
    $book['category'] = $category;
    $book['book_condition'] = $condition;
    $book['listing_type'] = $listingType;
    $book['description'] = $description;
}

// Shared header settings
$base = '../../';
$pageTitle = 'Edit Book';
$active = '';

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
body {
    background: #F8FAFC;
}

.edit-page {
    max-width: 760px;
    margin: 40px auto;
    padding-bottom: 50px;
}

.edit-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 4px 14px rgba(29, 53, 87, 0.05);
}

.edit-page h1 {
    color: #1D3557;
    margin-bottom: 8px;
}

.edit-subtitle {
    color: #6B7280;
    margin-bottom: 25px;
}

.edit-form label {
    display: block;
    color: #1F2937;
    font-weight: 600;
    margin-bottom: 8px;
}

.edit-form input,
.edit-form select,
.edit-form textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    margin-bottom: 20px;
    background: #FFFFFF;
    font: inherit;
    box-sizing: border-box;
}

.edit-form input:focus,
.edit-form select:focus,
.edit-form textarea:focus {
    outline: 2px solid #38BDF8;
    border-color: #38BDF8;
}

.edit-form textarea {
    min-height: 120px;
    resize: vertical;
}

.edit-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 5px;
}

.save-btn {
    background: #1D3557;
    color: #FFFFFF;
    border: none;
    padding: 12px 22px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.save-btn:hover {
    background: #294A75;
}

.cancel-btn {
    display: inline-block;
    text-decoration: none;
    background: #FFFFFF;
    color: #1D3557;
    border: 1px solid #1D3557;
    padding: 12px 22px;
    border-radius: 8px;
    font-weight: 600;
}

.cancel-btn:hover {
    background: #EFF6FF;
}

.edit-error {
    background: #FEE2E2;
    color: #991B1B;
    border: 1px solid #FECACA;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
}

@media (max-width: 600px) {
    .edit-card {
        padding: 20px;
    }

    .edit-page {
        margin-top: 25px;
    }
}
</style>

<div class="edit-page">

    <div class="edit-card">

        <h1>Edit Book</h1>

        <p class="edit-subtitle">
            Update the book listing details below.
        </p>

        <?php if ($error !== ''): ?>

            <div class="edit-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="edit_book.php?id=<?= (int)$bookId ?>" class="edit-form">

            <input
                type="hidden"
                name="bookId"
                value="<?= (int)$book['bookId'] ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <label for="title">Book Title</label>

            <input
                type="text"
                id="title"
                name="title"
                maxlength="255"
                value="<?= e($book['title']) ?>"
                required
            >

            <label for="author">Author</label>

            <input
                type="text"
                id="author"
                name="author"
                maxlength="255"
                value="<?= e($book['author'] ?? '') ?>"
                required
            >

            <label for="category">Category</label>

            <input
                type="text"
                id="category"
                name="category"
                maxlength="100"
                value="<?= e($book['category'] ?? '') ?>"
                required
            >

            <label for="book_condition">Book Condition</label>

            <select
                id="book_condition"
                name="book_condition"
                required
            >

                <?php foreach (['new', 'good', 'fair', 'poor'] as $c): ?>

                    <option
                        value="<?= e($c) ?>"
                        <?= ($book['book_condition'] ?? '') === $c ? 'selected' : '' ?>
                    >
                        <?= ucfirst($c) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <label for="listing_type">Listing Type</label>

            <select
                id="listing_type"
                name="listing_type"
                required
            >

                <option
                    value="exchange"
                    <?= ($book['listing_type'] ?? '') === 'exchange' ? 'selected' : '' ?>
                >
                    Exchange
                </option>

                <option
                    value="donate"
                    <?= ($book['listing_type'] ?? '') === 'donate' ? 'selected' : '' ?>
                >
                    Donate
                </option>

            </select>

            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
            ><?= e($book['description'] ?? '') ?></textarea>

            <div class="edit-actions">

                <button type="submit" class="save-btn">
                    Save Changes
                </button>

                <a href="books.php" class="cancel-btn">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
