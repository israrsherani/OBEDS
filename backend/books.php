<?php
include "db.php";

$requesterId = 1; // temporary, login ke baad session se aayega

$result = $conn->query("SELECT bookId, title FROM books
                        WHERE status='Available' AND ownerId != $requesterId");
?>

<h2>Available Books</h2>

<?php while ($row = $result->fetch_assoc()) { ?>
    <p><b><?php echo htmlspecialchars($row['title']); ?></b></p>
    <form method="POST" action="request_book.php">
        <input type="hidden" name="bookId" value="<?php echo $row['bookId']; ?>">
        <button type="submit">Request this book</button>
    </form>
    <hr>
<?php } ?>