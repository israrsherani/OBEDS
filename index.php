<?php
require __DIR__ . '/includes/db.php';

$pageTitle = 'Home';
$base      = '';          // this file is in the project root
$active    = 'home';

// Count how many available books there are of each type
$counts = ['exchange' => 0, 'donate' => 0];
$rows = $pdo->query("SELECT listing_type, COUNT(*) AS n FROM books WHERE status = 'available' GROUP BY listing_type")->fetchAll();
foreach ($rows as $r) {
    if (isset($counts[$r['listing_type']])) {
        $counts[$r['listing_type']] = (int) $r['n'];
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <h1>Give your books a second life.</h1>
    <p>Swap a book you have finished, or donate it to someone who needs it.</p>
    <div class="actions">
        <a class="btn" href="books/add_book.php">List a book</a>
        <a class="btn ghost" href="books/list_books.php">Browse books</a>
    </div>
</section>

<section class="counts" aria-label="Books available now">
    <a class="count-tile t-exchange" href="books/list_books.php?type=exchange">
        <strong><?= $counts['exchange'] ?></strong><span>books to exchange</span>
    </a>
    <a class="count-tile t-donate" href="books/list_books.php?type=donate">
        <strong><?= $counts['donate'] ?></strong><span>books given for free</span>
    </a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
