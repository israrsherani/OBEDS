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

<div class="page-head">
    <div>
        <h1 class="page-title">Edit Book</h1>
        <p>Update the book listing details below.</p>
    </div>
    <a class="btn soft" href="books.php" style="padding:10px 18px;font-size:.92rem">&larr; Back to Books</a>
</div>

<div class="form-narrow">
    <div class="card form-card">

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_book.php?id=<?= (int)$bookId ?>">

            <input type="hidden" name="bookId" value="<?= (int)$book['bookId'] ?>">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="form-grid">
                <div>
                    <label for="title">Book Title</label>
                    <input type="text" id="title" name="title" maxlength="255" value="<?= e($book['title']) ?>" required>
                </div>

                <div>
                    <label for="author">Author</label>
                    <input type="text" id="author" name="author" maxlength="255" value="<?= e($book['author'] ?? '') ?>" required>
                </div>

                <div>
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" maxlength="100" value="<?= e($book['category'] ?? '') ?>" required>
                </div>

                <div>
                    <label for="book_condition">Book Condition</label>
                    <select id="book_condition" name="book_condition" required>
                        <?php foreach (['new', 'good', 'fair', 'poor'] as $c): ?>
                            <option value="<?= e($c) ?>" <?= ($book['book_condition'] ?? '') === $c ? 'selected' : '' ?>>
                                <?= ucfirst($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="listing_type">Listing Type</label>
                    <select id="listing_type" name="listing_type" required>
                        <option value="exchange" <?= ($book['listing_type'] ?? '') === 'exchange' ? 'selected' : '' ?>>Exchange</option>
                        <option value="donate" <?= ($book['listing_type'] ?? '') === 'donate' ? 'selected' : '' ?>>Donate</option>
                    </select>
                </div>
            </div>

            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e($book['description'] ?? '') ?></textarea>

            <div class="btns" style="margin-top:26px">
                <button type="submit" class="btn primary">Save Changes</button>
                <a href="books.php" class="btn soft">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>