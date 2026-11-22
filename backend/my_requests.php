
<?php
session_start();
include "db.php";

// Check whether user is logged in
if (!isset($_SESSION["userId"])) {
    header("Location: ../frontend/login.html");
    exit;
}

$requesterId = (int) $_SESSION["userId"];

$stmt = $conn->prepare(
    "SELECT books.title,
            users.name AS ownerName,
            requests.status,
            requests.createdAt
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

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Requests - OBEDS</title>

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
    padding: 20px 7%;
    color: white;
}

.navbar h2 {
    margin: 0;
    font-size: 24px;
}

.navbar span {
    color: #38BDF8;
}

.container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
}

.heading {
    margin-bottom: 25px;
}

.heading h1 {
    font-size: 32px;
    margin-bottom: 10px;
}

.heading p {
    color: #64748B;
    line-height: 1.6;
}

.requests-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(29, 53, 87, 0.05);
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

thead {
    background: #1D3557;
    color: white;
}

th {
    text-align: left;
    padding: 17px 20px;
    font-size: 14px;
    font-weight: 600;
}

td {
    padding: 18px 20px;
    border-bottom: 1px solid #E2E8F0;
    color: #475569;
    font-size: 14px;
}

tbody tr:last-child td {
    border-bottom: none;
}

tbody tr:hover {
    background: #F8FAFC;
}

.book-title {
    color: #1D3557;
    font-weight: 600;
}

.owner {
    color: #38BDF8;
    font-weight: 500;
}

.status {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
}

.status-pending {
    background: #FFF4D6;
    color: #946200;
}

.status-accepted {
    background: #DDF4E4;
    color: #25653B;
}

.status-rejected {
    background: #FCE4E4;
    color: #A33333;
}

.status-default {
    background: #E0F2FE;
    color: #1D3557;
}

.empty {
    padding: 55px 25px;
    text-align: center;
}

.empty h3 {
    font-size: 22px;
    margin-bottom: 10px;
}

.empty p {
    color: #64748B;
    line-height: 1.7;
}

.footer {
    margin-top: 50px;
    text-align: center;
    padding: 25px 15px;
    color: #64748B;
    border-top: 1px solid #E2E8F0;
    font-size: 13px;
}

@media (max-width: 600px) {
    .container {
        margin: 25px auto;
    }

    .heading h1 {
        font-size: 26px;
    }
}
</style>
</head>

<body>

<div class="navbar">
    <h2>OBEDS <span>My Requests</span></h2>
</div>

<div class="container">

    <div class="heading">
        <h1>My Book Requests</h1>
        <p>Keep track of the books you've requested and their current status.</p>
    </div>

    <div class="requests-card">

        <?php if ($result->num_rows == 0) { ?>

            <div class="empty">
                <h3>No Requests Yet</h3>
                <p>You haven't sent any book requests yet.<br>
                Explore available books and start requesting!</p>
            </div>

        <?php } else { ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Requested On</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($row = $result->fetch_assoc()) { ?>

                            <?php
                            $status = strtolower(trim($row['status']));
                            $statusClass = 'status-default';

                            if ($status === 'pending') {
                                $statusClass = 'status-pending';
                            } elseif ($status === 'accepted') {
                                $statusClass = 'status-accepted';
                            } elseif ($status === 'rejected') {
                                $statusClass = 'status-rejected';
                            }
                            ?>

                            <tr>
                                <td class="book-title">
                                    <?= htmlspecialchars($row['title']) ?>
                                </td>

                                <td class="owner">
                                    <?= htmlspecialchars($row['ownerName']) ?>
                                </td>

                                <td>
                                    <span class="status <?= $statusClass ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['createdAt']) ?>
                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>
                </table>
            </div>

        <?php } ?>

    </div>

</div>

<div class="footer">
    OBEDS &mdash; a student project. Pass your books on.
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
