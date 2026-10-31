<?php
session_start();
require __DIR__ . '/../includes/db.php';

// TODO: once login is built, keep only $_SESSION['user_id']
$userId = $_SESSION['user_id'] ?? 1;

$errors = [];
$old = [
    'title' => '', 'author' => '', 'isbn' => '', 'category' => '',
    'book_condition' => 'good', 'listing_type' => 'exchange', 'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    // ---------- Validation ----------
    if ($old['title'] === '' || strlen($old['title']) > 200) {
        $errors[] = 'Title is required (max 200 characters).';
    }
    if ($old['author'] === '') {
        $errors[] = 'Author name is required.';
    }
    if ($old['category'] === '') {
        $errors[] = 'Please select a category.';
    }
    if (!in_array($old['book_condition'], ['new', 'good', 'fair', 'poor'], true)) {
        $errors[] = 'Invalid condition.';
    }
    if (!in_array($old['listing_type'], ['exchange', 'donate'], true)) {
        $errors[] = 'Invalid listing type.';
    }

    // ---------- Image (optional) ----------
    $coverName = null;
    if (!empty($_FILES['cover_image']['name'])) {
        $file = $_FILES['cover_image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'There was a problem uploading the image.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image must be smaller than 2MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                $errors[] = 'Only JPG, PNG or WEBP images are allowed.';
            } else {
                $coverName = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
            }
        }
    }

    // ---------- Save ----------
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>List a Book - OBEDS</title>
</head>
<body>
    <h1>List a Book</h1>
    <p><a href="list_books.php">&larr; Browse Books</a></p>

    <?php if ($errors): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <p><label>Title *<br>
            <input type="text" name="title" value="<?= e($old['title']) ?>" required></label></p>

        <p><label>Author *<br>
            <input type="text" name="author" value="<?= e($old['author']) ?>" required></label></p>

        <p><label>ISBN (optional)<br>
            <input type="text" name="isbn" value="<?= e($old['isbn']) ?>"></label></p>

        <p><label>Category *<br>
            <select name="category" required>
                <option value="">-- Select --</option>
                <?php foreach (['Fiction','Non-Fiction','Textbook','Competitive Exams','Comics','Self-Help','Science','Other'] as $c): ?>
                    <option value="<?= e($c) ?>" <?= $old['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select></label></p>

        <p><label>Condition *<br>
            <select name="book_condition">
                <?php foreach (['new' => 'New', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $v => $label): ?>
                    <option value="<?= $v ?>" <?= $old['book_condition'] === $v ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select></label></p>

        <p><label>Listing Type *<br>
            <select name="listing_type">
                <option value="exchange" <?= $old['listing_type'] === 'exchange' ? 'selected' : '' ?>>Exchange</option>
                <option value="donate" <?= $old['listing_type'] === 'donate' ? 'selected' : '' ?>>Donate</option>
            </select></label></p>

        <p><label>Description<br>
            <textarea name="description" rows="4" cols="40"><?= e($old['description']) ?></textarea></label></p>

        <p><label>Cover Image (JPG/PNG/WEBP, max 2MB)<br>
            <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp"></label></p>

        <button type="submit">List Book</button>
    </form>
</body>
</html>
