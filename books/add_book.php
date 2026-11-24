<?php

require_once __DIR__ . '/../includes/db.php';

// Login protection
$userId = current_user_id();

if ($userId === null) {
    header('Location: ../frontend/login.html');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$isbnRequired = ['Textbook'];

$categories = [
    'Fiction',
    'Non-Fiction',
    'Textbook',
    'Competitive Exams',
    'Comics',
    'Self-Help',
    'Science',
    'Other'
];

$errors = [];

$old = [
    'title' => '',
    'author' => '',
    'isbn' => '',
    'category' => '',
    'book_condition' => 'good',
    'listing_type' => 'exchange',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF verification
    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    }

    // Read form values
    foreach ($old as $key => $value) {
        $old[$key] = trim((string)($_POST[$key] ?? ''));
    }

    // Validate title
    if (
        $old['title'] === '' ||
        mb_strlen($old['title']) > 150
    ) {
        $errors[] = 'Enter a book title (maximum 150 characters).';
    }

    // Validate author
    if (
        $old['author'] === '' ||
        mb_strlen($old['author']) > 150
    ) {
        $errors[] = 'Enter an author name (maximum 150 characters).';
    }

    // Validate category
    if (!in_array($old['category'], $categories, true)) {
        $errors[] = 'Choose a valid category.';
    }

    // Validate condition
    if (!in_array(
        $old['book_condition'],
        ['new', 'good', 'fair', 'poor'],
        true
    )) {
        $errors[] = 'Choose a valid book condition.';
    }

    // Validate listing type
    if (!in_array(
        $old['listing_type'],
        ['exchange', 'donate'],
        true
    )) {
        $errors[] = 'Choose exchange or donate.';
    }

    // Validate ISBN
    if (
        in_array($old['category'], $isbnRequired, true) &&
        $old['isbn'] === ''
    ) {
        $errors[] = 'ISBN is required for Textbook books.';
    }

    if (
        $old['isbn'] !== '' &&
        !preg_match('/^[0-9Xx-]{10,17}$/', $old['isbn'])
    ) {
        $errors[] = 'Enter a valid ISBN format (10 to 13 characters, dashes allowed).';
    }

    // Validate description
    if (mb_strlen($old['description']) > 2000) {
        $errors[] = 'Description cannot exceed 2000 characters.';
    }

    // Image upload validation
    $coverName = null;
    $uploadedPath = null;

    if (
        isset($_FILES['cover_image']) &&
        $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['cover_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $errors[] = 'Image upload failed. Please try again.';

        } elseif (
            !is_uploaded_file($file['tmp_name']) ||
            $file['size'] > 2 * 1024 * 1024
        ) {

            $errors[] = 'Image must be a valid upload and smaller than 2 MB.';

        } else {

            $mime = (new finfo(FILEINFO_MIME_TYPE))
                ->file($file['tmp_name']);

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $imageInfo = @getimagesize($file['tmp_name']);

            if (
                !isset($allowed[$mime]) ||
                $imageInfo === false ||
                $imageInfo['mime'] !== $mime
            ) {

                $errors[] = 'Only valid JPG, PNG or WEBP images are allowed.';

            } else {

                $coverName = bin2hex(random_bytes(16))
                    . '.' . $allowed[$mime];

            }
        }
    }

    // Insert book if validation passed
    if (!$errors) {

        try {

            // Upload directory
            $uploadDirectory = __DIR__ . '/../assets/uploads/books/';

            if (
                !is_dir($uploadDirectory) &&
                !mkdir($uploadDirectory, 0755, true)
            ) {
                throw new Exception('Upload directory could not be created.');
            }

            // Move image
            if ($coverName !== null) {

                $uploadedPath = $uploadDirectory . $coverName;

                if (!move_uploaded_file(
                    $_FILES['cover_image']['tmp_name'],
                    $uploadedPath
                )) {
                    throw new Exception('Image could not be saved.');
                }
            }

            // Insert database record
            $stmt = $pdo->prepare(
                "INSERT INTO books
                (
                    ownerId,
                    title,
                    author,
                    isbn,
                    category,
                    book_condition,
                    listing_type,
                    description,
                    cover_image
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
                $coverName
            ]);

            // Redirect after successful insertion
            header('Location: list_books.php?msg=added');
            exit;

        } catch (Throwable $e) {

            // Remove uploaded image if database insertion fails
            if (
                $uploadedPath !== null &&
                file_exists($uploadedPath)
            ) {
                unlink($uploadedPath);
            }

            error_log('OBEDS add book error: ' . $e->getMessage());

            $errors[] = 'Your book could not be listed. Please try again.';
        }
    }
}

$pageTitle = 'List a book';
$active = 'add';
$base = '../';

require __DIR__ . '/../includes/header.php';

?>

<div class="form-narrow">

    <div class="page-head">
        <div>
            <h1 class="page-title">List a Book</h1>
            <p>
                Share a book with the OBEDS community.
                List it for exchange or donate it to another reader.
            </p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert error" role="alert">
            <strong>Please correct the following:</strong>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form
        class="card form-card"
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION['csrf_token']) ?>"
        >

        <div class="field">
            <label for="title">Book Title *</label>
            <input
                type="text"
                id="title"
                name="title"
                value="<?= e($old['title']) ?>"
                maxlength="150"
                placeholder="Enter the book title"
                required
            >
        </div>

        <div class="field">
            <label for="author">Author *</label>
            <input
                type="text"
                id="author"
                name="author"
                value="<?= e($old['author']) ?>"
                maxlength="150"
                placeholder="Enter the author's name"
                required
            >
        </div>

        <div class="form-grid">

            <div class="field">
                <label for="category">Category *</label>

                <select id="category" name="category" required>
                    <option value="">Choose a category</option>

                    <?php foreach ($categories as $c): ?>
                        <option
                            value="<?= e($c) ?>"
                            <?= $old['category'] === $c ? 'selected' : '' ?>
                        >
                            <?= e($c) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="field">
                <label for="isbn">
                    ISBN <span class="hint">(optional except Textbook)</span>
                </label>

                <input
                    type="text"
                    id="isbn"
                    name="isbn"
                    value="<?= e($old['isbn']) ?>"
                    placeholder="978-3-16-148410-0"
                >
            </div>

        </div>

        <div class="form-grid">

            <div class="field">
                <label for="book_condition">Book Condition *</label>

                <select id="book_condition" name="book_condition" required>

                    <?php foreach (
                        ['new' => 'New', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor']
                        as $v => $label
                    ): ?>

                        <option
                            value="<?= e($v) ?>"
                            <?= $old['book_condition'] === $v ? 'selected' : '' ?>
                        >
                            <?= e($label) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="field">
                <label for="listing_type">Listing Type *</label>

                <select id="listing_type" name="listing_type" required>

                    <option
                        value="exchange"
                        <?= $old['listing_type'] === 'exchange' ? 'selected' : '' ?>
                    >
                        Exchange for another book
                    </option>

                    <option
                        value="donate"
                        <?= $old['listing_type'] === 'donate' ? 'selected' : '' ?>
                    >
                        Donate for free
                    </option>

                </select>
            </div>

        </div>

        <div class="field">
            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
                rows="5"
                maxlength="2000"
                placeholder="Tell other readers about this book..."
            ><?= e($old['description']) ?></textarea>
        </div>

        <div class="field">
            <label for="cover_image">Book Cover Image</label>

            <input
                type="file"
                id="cover_image"
                name="cover_image"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >

            <p class="upload-hint">
                JPG, PNG or WEBP image. Maximum file size: 2 MB.
            </p>
        </div>

        <button class="btn gold block" type="submit">
            List My Book
        </button>

    </form>

    <a href="list_books.php" class="back-link">
        &larr; Back to Browse Books
    </a>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>