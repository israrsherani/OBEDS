
<?php

require_once __DIR__ . '/../includes/db.php';

// Validate book ID
$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$bookId || $bookId <= 0) {
    die("Invalid book ID.");
}

// Fetch book details
$stmt = $pdo->prepare(
    "SELECT * FROM books WHERE bookId = ?"
);

$stmt->execute([$bookId]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    die("Book not found.");
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Current user
$userId = current_user_id();

$isOwner = $userId !== null
    && (int)$userId === (int)$book['ownerId'];

$isAvailable = strtolower(trim($book['status'] ?? '')) === 'available';

$listingType = strtolower(trim($book['listing_type'] ?? 'exchange'));

$base = '../';
$pageTitle = 'Book Details';
$active = 'browse';

require_once __DIR__ . '/../includes/header.php';

?>

<style>
.book-details-page {
    max-width: 900px;
    margin: 45px auto;
    padding: 0 20px;
}

.book-details-page .page-heading {
    color: #1D3557;
    font-size: 30px;
    margin-bottom: 25px;
}

.details-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 38px;
    box-shadow: 0 8px 30px rgba(29, 53, 87, 0.08);
    border: 1px solid #e5edf5;
}

.book-title {
    color: #1D3557;
    font-size: 28px;
    margin-bottom: 12px;
    overflow-wrap: anywhere;
}

.author {
    color: #64748b;
    font-size: 16px;
    margin-bottom: 25px;
}

.badge {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 25px;
}

.exchange {
    background: #e0f2fe;
    color: #075985;
}

.donate {
    background: #dcfce7;
    color: #166534;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-bottom: 28px;
}

.detail-item {
    background: #F8FAFC;
    padding: 18px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.detail-item .label {
    display: block;
    color: #64748b;
    font-size: 13px;
    margin-bottom: 8px;
    font-weight: bold;
}

.detail-item .value {
    color: #1D3557;
    font-size: 16px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.description-section {
    margin-top: 10px;
    padding: 22px;
    border-radius: 12px;
    background: #F8FAFC;
    border: 1px solid #e2e8f0;
}

.description-section h3 {
    color: #1D3557;
    margin-bottom: 12px;
}

.description {
    line-height: 1.8;
    color: #475569;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
}

.description.empty {
    color: #94a3b8;
    font-style: italic;
}

.book-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 30px;
}

.request-btn,
.back-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: none;
    border-radius: 9px;
    padding: 14px 24px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
}

.request-btn {
    background: #1D3557;
    color: white;
}

.request-btn:hover {
    background: #27496f;
    transform: translateY(-1px);
}

.back-btn {
    background: #e0f2fe;
    color: #1D3557;
}

.back-btn:hover {
    background: #cde8f8;
}

.book-message {
    padding: 13px 16px;
    margin-top: 20px;
    border-radius: 9px;
    background: #F1F5F9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

@media (max-width: 600px) {
    .book-details-page {
        margin: 30px auto;
    }

    .details-card {
        padding: 24px 18px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .book-details-page .page-heading {
        font-size: 25px;
    }

    .book-title {
        font-size: 23px;
    }

    .book-actions {
        flex-direction: column;
    }

    .book-actions form,
    .request-btn,
    .back-btn {
        width: 100%;
    }
}
</style>

<div class="book-details-page">

    <h1 class="page-heading">Book Details</h1>

    <div class="details-card">

        <h2 class="book-title">
            <?= e($book['title']) ?>
        </h2>

        <p class="author">
            By <?= e($book['author'] ?: 'Unknown Author') ?>
        </p>

        <span class="badge <?= $listingType === 'donate' ? 'donate' : 'exchange' ?>">
            <?= e(ucfirst($listingType)) ?>
        </span>

        <div class="details-grid">

            <div class="detail-item">
                <span class="label">ISBN</span>
                <span class="value">
                    <?= e($book['isbn'] ?: 'Not provided') ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="label">Category</span>
                <span class="value">
                    <?= e($book['category'] ?: 'Not provided') ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="label">Book Condition</span>
                <span class="value">
                    <?= e(ucfirst($book['book_condition'] ?: 'Not provided')) ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="label">Listing Type</span>
                <span class="value">
                    <?= e(ucfirst($listingType)) ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="label">Availability</span>
                <span class="value">
                    <?= $isAvailable ? 'Available' : 'Not Available' ?>
                </span>
            </div>

        </div>

        <div class="description-section">

            <h3>Description</h3>

            <?php if (!empty($book['description'])): ?>

                <p class="description">
                    <?= e($book['description']) ?>
                </p>

            <?php else: ?>

                <p class="description empty">
                    No description provided.
                </p>

            <?php endif; ?>

        </div>

        <div class="book-actions">

            <?php if ($isAvailable && $userId === null): ?>

                <a href="../frontend/login.html" class="request-btn">
                    Login to Request This Book
                </a>

            <?php elseif ($isAvailable && !$isOwner): ?>

                <form method="POST" action="../backend/request_book.php">

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

                    <button type="submit" class="request-btn">
                        Request This Book
                    </button>

                </form>

            <?php elseif ($isOwner): ?>

                <div class="book-message">
                    This is your own listed book.
                </div>

            <?php else: ?>

                <div class="book-message">
                    This book is currently not available.
                </div>

            <?php endif; ?>

            <a href="search_books.php" class="back-btn">
                &larr; Back to Browse Books
            </a>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
