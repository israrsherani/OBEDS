<?php
include "db.php";

$requestId = (int)$_POST['requestId'];
$action = $_POST['action'];

if ($action == "accept") {
    $conn->query("UPDATE requests SET status='Accepted' WHERE requestId=$requestId");
    $conn->query("UPDATE books SET status='Not Available'
                  WHERE bookId = (SELECT bookId FROM requests WHERE requestId=$requestId)");
} elseif ($action == "reject") {
    $conn->query("UPDATE requests SET status='Rejected' WHERE requestId=$requestId");
}

header("Location: requests.php");
?>