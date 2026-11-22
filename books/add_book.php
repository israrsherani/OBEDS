
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

require __DIR__ . '/../includes/header.php';

?>

<style>
.addbook-page {
    max-width: 850px;
    margin: 40px auto;
    padding: 0 20px;
}

.addbook-page * {
    box-sizing: border-box;
}

.addbook-heading {
    margin-bottom: 25px;
}

.addbook-heading h1 {
    color: #1D3557;
    font-size: 32px;
    margin-bottom: 10px;
}

.addbook-heading p {
    color: #64748b;
    line-height: 1.6;
}

.addbook-card {
    background: #fff;
    padding: 35px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 30px rgba(29, 53, 87, 0.06);
}

.addbook-page .field {
    margin-bottom: 22px;
}

.addbook-page .row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
}

.addbook-page label {
    display: block;
    font-weight: 600;
    color: #1D3557;
    font-size: 14px;
    margin-bottom: 9px;
}

.addbook-page input[type="text"],
.addbook-page select,
.addbook-page textarea {
    width: 100%;
    padding: 13px 15px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 15px;
    color: #334155;
    background: #fff;
    outline: none;
    font-family: inherit;
}

.addbook-page input:focus,
.addbook-page select:focus,
.addbook-page textarea:focus {
    border-color: #38BDF8;
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.12);
}

.addbook-page textarea {
    resize: vertical;
    min-height: 130px;
    line-height: 1.6;
}

.addbook-page .hint {
    color: #64748b;
    font-size: 12px;
    font-weight: normal;
}

.addbook-page input[type="file"] {
    width: 100%;
    padding: 15px;
    background: #F8FAFC;
    border: 1px dashed #94a3b8;
    border-radius: 10px;
    color: #475569;
    cursor: pointer;
}

.addbook-page input[type="file"]::file-selector-button {
    background: #e0f2fe;
    color: #1D3557;
    border: none;
    border-radius: 7px;
    padding: 10px 15px;
    margin-right: 12px;
    font-weight: 600;
    cursor: pointer;
}

.addbook-upload-hint {
    font-size: 13px;
    color: #64748b;
    margin-top: 10px;
    line-height: 1.6;
}

.addbook-errors {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 18px 22px;
    margin-bottom: 22px;
}

.addbook-errors ul {
    padding-left: 20px;
    line-height: 1.8;
}

.addbook-submit {
    width: 100%;
    background: #1D3557;
    color: white;
    padding: 15px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
}

.addbook-submit:hover {
    background: #294b72;
    transform: translateY(-1px);
}

.addbook-back {
    display: inline-block;
    margin-top: 22px;
    color: #1D3557;
    font-weight: 600;
    text-decoration: none;
}

.addbook-back:hover {
    color: #38BDF8;
}

@media (max-width: 650px) {
    .addbook-page {
        margin: 25px auto;
        padding: 0 15px;
    }

    .addbook-heading h1 {
        font-size: 26px;
    }

    .addbook-card {
        padding: 22px 18px;
    }

    .addbook-page .row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<div class="addbook-page">

    <div class="addbook-heading">
        <h1>List a Book</h1>
        <p>
            Share a book with the OBEDS community.
            List it for exchange or donate it to another reader.
        </p>
    </div>

    <?php if ($errors): ?>
        <div class="addbook-errors" role="alert">
            <strong>Please correct the following:</strong>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form
        class="addbook-card"
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

        <div class="row">

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

        <div class="row">

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

            <p class="addbook-upload-hint">
                JPG, PNG or WEBP image. Maximum file size: 2 MB.
            </p>
        </div>

        <button class="addbook-submit" type="submit">
            List My Book
        </button>

    </form>

    <a href="list_books.php" class="addbook-back">
        &larr; Back to Browse Books
    </a>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
