<?php
include "db.php";

$requesterId = 1; // temporary, login ke baad session se aayega

$result = $conn->query("SELECT bookId, title, author, isbn, category,
                        book_condition, listing_type, description, cover_image
                        FROM books
                        WHERE status='Available' AND ownerId != $requesterId");

?>

<h2>Available Books</h2>

<?php while ($row = $result->fetch_assoc()) { ?>
    <h3><?php echo htmlspecialchars($row['title']); ?></h3>

<p><b>Author:</b> <?php echo htmlspecialchars($row['author'] ?? ''); ?></p>

<p><b>ISBN:</b> <?php echo htmlspecialchars($row['isbn'] ?? ''); ?></p>

<p><b>Category:</b> <?php echo htmlspecialchars($row['category'] ?? ''); ?></p>

<p><b>Condition:</b> <?php echo htmlspecialchars($row['book_condition'] ?? ''); ?></p>

<p><b>Listing Type:</b> <?php echo htmlspecialchars($row['listing_type'] ?? ''); ?></p>

<p><b>Description:</b> <?php echo htmlspecialchars($row['description'] ?? ''); ?></p>
    <form method="POST" action="request_book.php">
        <input type="hidden" name="bookId" value="<?php echo $row['bookId']; ?>">
        <button type="submit">Request this book</button>
    </form>
    <hr>
<?php } ?>