
<?php
require_once __DIR__ . '/../includes/db.php';

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$type = trim($_GET['type'] ?? '');

$sql = "SELECT bookId, title, author, category, book_condition, listing_type, description 
        FROM books WHERE status = 'Available'";
$params = [];

if ($q !== '') {
    $sql .= " AND (title LIKE :q1 OR author LIKE :q2)";
    $params[':q1'] = "%$q%";
    $params[':q2'] = "%$q%";
}

if ($category !== '') {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

if ($type !== '') {
    $sql .= " AND listing_type = :type";
    $params[':type'] = $type;
}

$sql .= " ORDER BY title";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query(
    "SELECT DISTINCT category FROM books 
     WHERE category IS NOT NULL AND category <> '' 
     ORDER BY category"
)->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Search Books - OBEDS</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #F8FAFC;
    color: #1D3557;
}

.navbar {
    background: #1D3557;
    padding: 18px 7%;
    color: white;
}

.navbar h2 {
    margin: 0;
    font-size: 23px;
}

.navbar span {
    color: #A8DADC;
}

.container {
    max-width: 1150px;
    margin: 40px auto;
    padding: 0 20px;
}

.header {
    margin-bottom: 25px;
}

.header h1 {
    margin-bottom: 10px;
    font-size: 32px;
}

.header p {
    color: #64748B;
    margin: 0;
    line-height: 1.6;
}

.search-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(29, 53, 87, 0.05);
}

.search-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 18px;
}

.field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

.field input,
.field select {
    width: 100%;
    padding: 12px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    background: white;
    color: #1D3557;
    font-size: 15px;
    outline: none;
}

.field input:focus,
.field select:focus {
    border-color: #38BDF8;
    box-shadow: 0 0 0 3px rgba(69, 123, 157, 0.12);
}

.search-actions {
    display: flex;
    gap: 12px;
    margin-top: 22px;
}

.btn {
    display: inline-block;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: 0.2s;
}

.btn-primary {
    background: #1D3557;
    color: white;
}

.btn-primary:hover {
    background: #28476D;
}

.btn-secondary {
    background: #F0F9FF;
    color: #1D3557;
}

.btn-secondary:hover {
    background: #E0F2FE;
}

.results-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.results-heading h2 {
    margin: 0;
    font-size: 22px;
}

.count {
    color: #64748B;
    font-size: 14px;
}

.books-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.book-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(29, 53, 87, 0.04);
    transition: 0.2s;
}

.book-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(29, 53, 87, 0.08);
}

.book-card h3 {
    margin: 0 0 10px;
    font-size: 20px;
    line-height: 1.4;
}

.book-card h3 a {
    text-decoration: none;
    color: #1D3557;
}

.book-card h3 a:hover {
    color: #38BDF8;
}

.author {
    color: #64748B;
    margin-bottom: 18px;
}

.meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 18px;
}

.tag {
    background: #F0F9FF;
    color: #1D3557;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.tag-type {
    background: #E0F2FE;
    color: #1D3557;
}

.description {
    color: #475569;
    font-size: 14px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.details-link {
    display: inline-block;
    margin-top: 18px;
    text-decoration: none;
    color: #38BDF8;
    font-weight: 600;
    font-size: 14px;
}

.details-link:hover {
    text-decoration: underline;
}

.empty {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 50px 20px;
    text-align: center;
}

.empty h3 {
    margin-top: 0;
}

.empty p {
    color: #64748B;
}

.footer {
    text-align: center;
    padding: 25px 15px;
    color: #64748B;
    font-size: 13px;
    margin-top: 50px;
    border-top: 1px solid #E2E8F0;
}

@media (max-width: 750px) {
    .search-grid {
        grid-template-columns: 1fr;
    }

    .books-grid {
        grid-template-columns: 1fr;
    }

    .header h1 {
        font-size: 26px;
    }
}

@media (max-width: 480px) {
    .search-actions {
        flex-direction: column;
    }

    .search-actions .btn {
        text-align: center;
    }

    .container {
        margin: 25px auto;
    }

    .search-card {
        padding: 18px;
    }
}
</style>
</head>

<body>

<div class="navbar">
    <h2>OBEDS <span>Book Search</span></h2>
</div>

<div class="container">

    <div class="header">
        <h1>Find Your Next Book</h1>
        <p>Search, explore and discover books available for exchange or donation.</p>
    </div>

    <div class="search-card">

        <form method="GET" action="search_books.php">

            <div class="search-grid">

                <div class="field">
                    <label for="q">Title or Author</label>
                    <input type="text" id="q" name="q"
                           placeholder="Search by title or author..."
                           value="<?= e($q) ?>">
                </div>

                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>

                        <?php foreach ($categories as $c): ?>
                            <option value="<?= e($c) ?>"
                                <?= $category === $c ? 'selected' : '' ?>>
                                <?= e($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="type">Listing Type</label>
                    <select id="type" name="type">
                        <option value="">All Types</option>

                        <option value="exchange"
                            <?= $type === 'exchange' ? 'selected' : '' ?>>
                            Exchange
                        </option>

                        <option value="donate"
                            <?= $type === 'donate' ? 'selected' : '' ?>>
                            Donate
                        </option>
                    </select>
                </div>

            </div>

            <div class="search-actions">
                <button type="submit" class="btn btn-primary">
                    Search Books
                </button>

                <a href="search_books.php" class="btn btn-secondary">
                    Clear Filters
                </a>

                <a href="list_books.php" class="btn btn-secondary">
                    Browse Books
                </a>
            </div>

        </form>

    </div>

    <div class="results-heading">
        <h2>Search Results</h2>
        <span class="count"><?= count($books) ?> books found</span>
    </div>

    <?php if (empty($books)): ?>

        <div class="empty">
            <h3>No Books Found</h3>
            <p>Try changing your search keywords or filters.</p>
        </div>

    <?php else: ?>

        <div class="books-grid">

            <?php foreach ($books as $b): ?>

                <div class="book-card">

                    <h3>
                        <a href="book_details.php?id=<?= urlencode($b['bookId']) ?>">
                            <?= e($b['title']) ?>
                        </a>
                    </h3>

                    <div class="author">
                        By <?= e($b['author']) ?>
                    </div>

                    <div class="meta">
                        <span class="tag">
                            <?= e($b['category']) ?>
                        </span>

                        <span class="tag">
                            <?= e($b['book_condition']) ?>
                        </span>

                        <span class="tag tag-type">
                            <?= e(ucfirst($b['listing_type'])) ?>
                        </span>
                    </div>

                    <div class="description">
                        <?= e($b['description'] ?: 'No description available.') ?>
                    </div>

                    <a class="details-link"
                       href="book_details.php?id=<?= urlencode($b['bookId']) ?>">
                        View Book Details &rarr;
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<div class="footer">
    OBEDS &mdash; a student project. Pass your books on.
</div>

</body>
</html>
