<?php
include "db.php";

$ownerId = 2;

$sql = "SELECT requests.requestId, requests.status, books.title, users.name
        FROM requests
        JOIN books ON requests.bookId = books.bookId
        JOIN users ON requests.requesterId = users.userId
        WHERE requests.ownerId = $ownerId";

$result = $conn->query($sql);
?>

<h2>Incoming Requests</h2>

<?php while ($row = $result->fetch_assoc()) { ?>
    <p>
        <b><?php echo $row['name']; ?></b> requested
        <b><?php echo $row['title']; ?></b>
        - Status: <?php echo $row['status']; ?>
    </p>

    <form method="POST" action="update_request.php">
        <input type="hidden" name="requestId" value="<?php echo $row['requestId']; ?>">
        <button type="submit" name="action" value="accept">Accept</button>
        <button type="submit" name="action" value="reject">Reject</button>
    </form>
    <hr>
<?php } ?>