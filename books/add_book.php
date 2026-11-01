<?php
require __DIR__ . '/../includes/db.php';

$userId = current_user_id();

// Categories where the ISBN number is compulsory.
// To add more later, just add the name here, e.g. ['Textbook', 'Competitive Exams']
$isbnRequired = ['Textbook'];

$categories = ['Fiction', 'Non-Fiction', 'Textbook', 'Competitive Exams', 'Comics', 'Self-Help', 'Science', 'Other'];

$errors = [];
$old = [
    'title' => '', 'author' => '', 'isbn' => '', 'category' => '',
    'book_condition' => 'good', 'listing_type' => 'exchange',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read every field from the form
    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    // ---------- Validation (checking the data) ----------
    if ($old['title'] === '' || strlen($old['title']) > 200) {
        $errors[] = 'Enter a title (up to 200 characters).';
    }
    if ($old['author'] === '') {
        $errors[] = 'Enter the author name.';
    }
    if (!in_array($old['category'], $categories, true)) {
        $errors[] = 'Choose a category.';
    }
    if (!in_array($old['book_condition'], ['new', 'good', 'fair', 'poor'], true)) {
        $errors[] = 'Choose a valid condition.';
    }
    if (!in_array($old['listing_type'], ['exchange', 'donate'], true)) {
        $errors[] = 'Choose exchange or donate.';
    }

    // ISBN is compulsory for some categories (e.g. Textbook)
    if (in_array($old['category'], $isbnRequired, true) && $old['isbn'] === '') {
        $errors[] = 'Enter the ISBN. It is required for ' . $old['category'] . ' books.';
    }
    // If an ISBN is typed, it must look like one (digits, X and dashes only)
    if ($old['isbn'] !== '' && !preg_match('/^[0-9Xx-]{10,17}$/', $old['isbn'])) {
        $errors[] = 'The ISBN should be 10 to 13 digits. Dashes are allowed.';
    }

    // ---------- Cover image (optional) ----------
    $coverName = null;
    if (!empty($_FILES['cover_image']['name'])) {
        $file = $_FILES['cover_image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The image could not be uploaded. Try again.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'The image is larger than 2 MB. Choose a smaller one.';
        } else {
            // Check the real file type, not just the name
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                $errors[] = 'Use a JPG, PNG or WEBP image.';
            } else {
                // Random file name so two uploads never clash
                $coverName = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
            }
        }
    }

    // ---------- Save to the database ----------
    if (!$errors) {
        if ($coverName !== null) {
            move_uploaded_file(
                $_FILES['cover_image']['tmp_name'],
                __DIR__ . '/../assets/uploads/books/' . $coverName
            );
        }

        $stmt = $pdo->prepare(
            'INSERT INTO books
             (user_id, title, author, isbn, category, book_condition, listing_type, description, cover_image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $old['title'],
            $old['author'],
            $old['isbn'] !== '' ? $old['isbn'] : null,
            $old['category'],
            $old['book_condition'],
            $old['listing_type'],
            $old['description'],
            $coverName,
        ]);

        header('Location: list_books.php?msg=added');
        exit;
    }
}

$pageTitle = 'List a book';
$active    = 'add';
require __DIR__ . '/../includes/header.php';
?>
<h1>List a book</h1>

<?php if ($errors): ?>
    <div class="notice err" role="alert">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="form-card" method="post" enctype="multipart/form-data">
    <div class="field">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($old['title']) ?>" required>
    </div>

    <div class="field">
        <label for="author">Author</label>
        <input type="text" id="author" name="author" value="<?= e($old['author']) ?>" required>
    </div>

    <div class="row">
        <div class="field">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Choose a category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c) ?>" <?= $old['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="isbn">ISBN <span class="hint" id="isbn-hint">(optional)</span></label>
            <input type="text" id="isbn" name="isbn" value="<?= e($old['isbn']) ?>" placeholder="978-3-16-148410-0">
        </div>
    </div>

    <div class="row">
        <div class="field">
            <label for="book_condition">Condition</label>
            <select id="book_condition" name="book_condition">
                <?php foreach (['new' => 'New', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $v => $label): ?>
                    <option value="<?= $v ?>" <?= $old['book_condition'] === $v ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="listing_type">What do you want to do with it?</label>
            <select id="listing_type" name="listing_type">
                <option value="exchange" <?= $old['listing_type'] === 'exchange' ? 'selected' : '' ?>>Exchange it for another book</option>
                <option value="donate" <?= $old['listing_type'] === 'donate' ? 'selected' : '' ?>>Donate it for free</option>
            </select>
        </div>
    </div>

    <div class="field">
        <label for="description">Description <span class="hint">(optional)</span></label>
        <textarea id="description" name="description" rows="4"><?= e($old['description']) ?></textarea>
    </div>

    <div class="field">
        <label for="cover_image">Cover photo <span class="hint">(optional, JPG, PNG or WEBP, up to 2 MB)</span></label>
        <input type="file" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/webp">
    </div>

    <button class="btn" type="submit">List book</button>
</form>

<script>
    // Categories that need an ISBN (same list as in PHP above)
    var isbnRequired = <?= json_encode($isbnRequired) ?>;

    function updateForm() {
        // 1. ISBN: compulsory for Textbook, optional for the others
        var category = document.getElementById('category').value;
        var needIsbn = isbnRequired.indexOf(category) !== -1;
        document.getElementById('isbn').required = needIsbn;
        document.getElementById('isbn-hint').textContent = needIsbn ? '(required for this category)' : '(optional)';

    }

    // Run when the category changes, and once when the page opens
    document.getElementById('category').addEventListener('change', updateForm);
    updateForm();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
