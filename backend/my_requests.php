<?php
include "db.php";

$requesterId = 1; // temporary, login ban jaye to logged-in user ki id lagayenge

$stmt = $conn->prepare(
    "SELECT books.title, users.name AS ownerName, requests.status, requests.createdAt
     FROM requests
     JOIN books ON requests.bookId = books.bookId
     JOIN users ON requests.ownerId = users.userId
     WHERE requests.requesterId = ?
     ORDER BY requests.createdAt DESC"
);
$stmt->bind_param("i", $requesterId);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>My Requests</h2>

<?php if ($result->num_rows == 0) { ?>
    <p>You have not sent any requests yet.</p>
<?php } else { ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Book</th>
            <th>Owner</th>
            <th>Status</th>
            <th>Requested On</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['ownerName']); ?></td>
                <td><b><?php echo htmlspecialchars($row['status']); ?></b></td>
                <td><?php echo htmlspecialchars($row['createdAt']); ?></td>
            </tr>
        <?php } ?>
    </table>
<?php } ?>