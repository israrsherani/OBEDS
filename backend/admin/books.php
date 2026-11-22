
<?php
require_once __DIR__ . '/../../includes/db.php';

// Only admins can access this page
if (!is_admin()) {
    http_response_code(403);
    exit("Access denied. Admins only.");
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle book deletion
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST['csrf_token'] ?? '';

    // Verify CSRF token
    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit("Invalid request.");
    }

    $bookId = filter_input(INPUT_POST, 'bookId', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if ($action !== 'delete' || !$bookId || $bookId <= 0) {
        header("Location: books.php?status=invalid");
        exit;
    }

    // Delete selected book
    $stmt = $pdo->prepare(
        "DELETE FROM books WHERE bookId = ?"
    );

    $stmt->execute([$bookId]);

    if ($stmt->rowCount() > 0) {
        header("Location: books.php?status=deleted");
    } else {
        header("Location: books.php?status=notfound");
    }

    exit;
}

// Fetch all books with owner information
$stmt = $pdo->query(
    "SELECT
        b.bookId,
        b.title,
        b.author,
        u.name AS ownerName,
        u.email AS ownerEmail,
        b.category,
        b.status,
        b.listing_type,
        b.created_at
     FROM books b
     LEFT JOIN users u ON b.ownerId = u.userId
     ORDER BY b.bookId DESC"
);

$books = $stmt->fetchAll();

// Shared header settings
$base = '../../';
$pageTitle = 'Manage Books';
$active = '';

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
body {
    background: #F8FAFC;
}

.admin-books-page {
    padding: 40px 0 60px;
}

.page-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.page-head h1 {
    color: #1D3557;
    font-size: 32px;
    margin: 0;
}

.back-btn {
    display: inline-block;
    background: #FFFFFF;
    border: 1px solid #1D3557;
    color: #1D3557;
    text-decoration: none;
    border-radius: 8px;
    padding: 10px 17px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.back-btn:hover {
    background: #EFF6FF;
}

.books-summary {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(29, 53, 87, 0.04);
}

.books-summary strong {
    color: #1D3557;
    font-size: 22px;
}

.books-summary span {
    color: #6B7280;
    margin-left: 8px;
}

.notice {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 22px;
    font-size: 14px;
    font-weight: 600;
}

.notice.ok {
    background: #DCFCE7;
    color: #166534;
    border: 1px solid #BBF7D0;
}

.notice.err {
    background: #FEE2E2;
    color: #991B1B;
    border: 1px solid #FECACA;
}

.books-table-container {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    overflow-x: auto;
    box-shadow: 0 4px 14px rgba(29, 53, 87, 0.05);
}

.books-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1150px;
}

.books-table th {
    background: #1D3557;
    color: #FFFFFF;
    text-align: left;
    padding: 16px;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
}

.books-table td {
    padding: 16px;
    border-bottom: 1px solid #E2E8F0;
    color: #4B5563;
    font-size: 14px;
    vertical-align: middle;
}

.books-table tbody tr:last-child td {
    border-bottom: none;
}

.books-table tbody tr:hover {
    background: #F8FAFC;
}

.book-status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.book-available {
    background: #DCFCE7;
    color: #166534;
}

.book-unavailable {
    background: #F1F5F9;
    color: #475569;
}

.book-type {
    display: inline-block;
    background: #EFF6FF;
    color: #1D3557;
    border: 1px solid #DBEAFE;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 600;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.edit-btn,
.delete-btn {
    display: inline-block;
    text-decoration: none;
    border: none;
    border-radius: 8px;
    padding: 9px 15px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.edit-btn {
    background: #DBEAFE;
    color: #1D3557;
}

.edit-btn:hover {
    background: #BFDBFE;
}

.delete-btn {
    background: #FEE2E2;
    color: #991B1B;
}

.delete-btn:hover {
    background: #FECACA;
}

.delete-form {
    margin: 0;
}

.empty-books {
    background: #FFFFFF;
    border: 1px dashed #CBD5E1;
    border-radius: 14px;
    padding: 35px;
    text-align: center;
    color: #6B7280;
}

@media (max-width: 600px) {
    .admin-books-page {
        padding: 25px 0 40px;
    }

    .page-head h1 {
        font-size: 26px;
    }

    .page-head {
        align-items: flex-start;
    }

    .books-summary {
        padding: 18px;
    }
}
</style>

<div class="admin-books-page">

    <div class="page-head">

        <h1>Manage Books</h1>

        <a class="back-btn" href="dashboard.php">
            &larr; Back to Dashboard
        </a>

    </div>

    <div class="books-summary">
        <strong><?= count($books) ?></strong>
        <span>Book Listings</span>
    </div>

    <!-- Status Messages -->
    <?php if (isset($_GET['status'])): ?>

        <?php if ($_GET['status'] === 'deleted'): ?>

            <div class="notice ok">
                Book listing deleted successfully.
            </div>

        <?php elseif ($_GET['status'] === 'notfound'): ?>

            <div class="notice err">
                Book not found or already deleted.
            </div>

        <?php elseif ($_GET['status'] === 'invalid'): ?>

            <div class="notice err">
                Invalid book deletion request.
            </div>

        <?php endif; ?>

    <?php endif; ?>

    <!-- Books Table -->
    <?php if (empty($books)): ?>

        <div class="empty-books">
            No books found.
        </div>

    <?php else: ?>

        <div class="books-table-container">

            <table class="books-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Owner Name</th>
                        <th>Owner Email</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($books as $book): ?>

                        <tr>

                            <td><?= e($book['bookId']) ?></td>

                            <td><?= e($book['title']) ?></td>

                            <td><?= e($book['author']) ?></td>

                            <td><?= e($book['ownerName'] ?? 'Unknown') ?></td>

                            <td><?= e($book['ownerEmail'] ?? 'Unknown') ?></td>

                            <td><?= e($book['category']) ?></td>

                            <td>
                                <span class="book-type">
                                    <?= e($book['listing_type']) ?>
                                </span>
                            </td>

                            <td>

                                <?php if (
                                    strtolower(trim((string)$book['status'])) === 'available'
                                ): ?>

                                    <span class="book-status book-available">
                                        <?= e($book['status']) ?>
                                    </span>

                                <?php else: ?>

                                    <span class="book-status book-unavailable">
                                        <?= e($book['status']) ?>
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="action-buttons">

                                    <a
                                        class="edit-btn"
                                        href="edit_book.php?id=<?= (int)$book['bookId'] ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="books.php"
                                        class="delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete this book listing?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="bookId"
                                            value="<?= (int)$book['bookId'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <button
                                            type="submit"
                                            class="delete-btn"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
