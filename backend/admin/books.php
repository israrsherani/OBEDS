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
/* Admin pages - site theme (colors come from style.css variables) */
.count-bar{display:flex;align-items:baseline;gap:10px;background:var(--card);border:1px solid var(--line);border-top:3px solid var(--gold);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px 24px;margin-bottom:22px}
.count-bar strong{font-family:var(--serif);font-size:2rem;line-height:1}
.count-bar span{color:var(--muted)}
.page-head .btn.soft{padding:10px 18px;font-size:.92rem}

.table-card{background:var(--card);border:1px solid var(--line);border-top:3px solid var(--gold);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.admin-table{width:100%;border-collapse:collapse;box-shadow:none;border-radius:0;background:none}
.admin-table th{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff;text-align:left;padding:14px 14px;font-size:.85rem;font-weight:600;white-space:nowrap}
.admin-table td{padding:14px;border-bottom:1px solid var(--line);color:var(--text);font-size:.92rem;vertical-align:middle;overflow-wrap:anywhere}
.admin-table tbody tr:last-child td{border-bottom:0}
.admin-table tbody tr:hover{background:rgba(224,165,38,.08)}
.admin-table .id{color:var(--muted);width:48px}
.admin-table .main{font-weight:600}
.admin-table .sub{display:block;color:var(--muted);font-size:.82rem;font-weight:400;margin-top:2px}
.admin-table .actions-cell{white-space:nowrap}

.pill{display:inline-block;padding:5px 12px;border-radius:99px;font-size:.76rem;font-weight:700;white-space:nowrap}
.pill.ok{background:#d9f3e4;color:#17683d}
.pill.bad{background:#fde2e4;color:#9b1c2c}
.pill.off{background:#e8eaf3;color:#475069}
.pill.type{background:#e3e8ff;color:#2a3b9a}
.pill.admin{background:#fff4d6;color:#946200}

.row-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.row-actions form{margin:0}
.tbtn{display:inline-block;border:0;border-radius:10px;padding:8px 14px;font:inherit;font-size:.82rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:filter .2s}
.tbtn:hover{filter:brightness(.95)}
.tbtn.edit{background:#e3e8ff;color:#243074}
.tbtn.danger{background:#fde2e4;color:#9b1c2c}
.tbtn.restore{background:#d9f3e4;color:#17683d}

/* mobile: each row becomes a card, no sideways scroll */
@media (max-width:820px){
  .admin-table,.admin-table tbody,.admin-table tr,.admin-table td{display:block;width:100%}
  .admin-table thead{display:none}
  .admin-table tr{padding:14px 16px;border-bottom:1px solid var(--line)}
  .admin-table tbody tr:last-child{border-bottom:0}
  .admin-table td{border:0;padding:6px 0;display:flex;justify-content:space-between;align-items:center;gap:14px;text-align:right}
  .admin-table td::before{content:attr(data-label);font-weight:600;color:var(--muted);font-size:.8rem;text-align:left;flex:0 0 auto}
  .admin-table .id{width:100%}
  .admin-table .sub{text-align:right}
  .row-actions{justify-content:flex-end}
}
</style>


<div class="page-head">
    <div>
        <h1 class="page-title">Manage Books</h1>
        <p>Edit or remove book listings.</p>
    </div>
    <a class="btn soft" href="dashboard.php">&larr; Back to Dashboard</a>
</div>

<div class="count-bar">
    <strong><?= count($books) ?></strong>
    <span>Book Listings</span>
</div>

<!-- Status Messages -->
<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] === 'deleted'): ?>
        <div class="alert success">Book listing deleted successfully.</div>
    <?php elseif ($_GET['status'] === 'notfound'): ?>
        <div class="alert error">Book not found or already deleted.</div>
    <?php elseif ($_GET['status'] === 'invalid'): ?>
        <div class="alert error">Invalid book deletion request.</div>
    <?php endif; ?>
<?php endif; ?>

<!-- Books Table -->
<?php if (empty($books)): ?>

    <div class="empty">
        <h3>No books found</h3>
        <p>Book listings will appear here.</p>
    </div>

<?php else: ?>

    <div class="table-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Book</th>
                    <th>Owner</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td class="id" data-label="ID">#<?= (int)$book['bookId'] ?></td>

                        <td data-label="Book">
                            <span class="main"><?= e($book['title']) ?></span>
                            <span class="sub"><?= e($book['author']) ?></span>
                        </td>

                        <td data-label="Owner">
                            <span class="main"><?= e($book['ownerName'] ?? 'Unknown') ?></span>
                            <span class="sub"><?= e($book['ownerEmail'] ?? 'Unknown') ?></span>
                        </td>

                        <td data-label="Category"><?= e($book['category']) ?></td>

                        <td data-label="Type">
                            <span class="pill type"><?= e($book['listing_type']) ?></span>
                        </td>

                        <td data-label="Status">
                            <?php if (strtolower(trim((string)$book['status'])) === 'available'): ?>
                                <span class="pill ok"><?= e($book['status']) ?></span>
                            <?php else: ?>
                                <span class="pill off"><?= e($book['status']) ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="actions-cell" data-label="Action">
                            <div class="row-actions">
                                <a class="tbtn edit" href="edit_book.php?id=<?= (int)$book['bookId'] ?>">Edit</a>

                                <form
                                    method="POST"
                                    action="books.php"
                                    onsubmit="return confirm('Are you sure you want to delete this book listing?');"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="bookId" value="<?= (int)$book['bookId'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="tbtn danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>