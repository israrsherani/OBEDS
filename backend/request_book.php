<?php
include "db.php";

$requesterId = 1; // temporary, login ke baad session se aayega
$bookId = (int)$_POST['bookId'];

$result = $conn->query("SELECT ownerId FROM books WHERE bookId=$bookId AND status='Available'");
$book = $result->fetch_assoc();

$check = $conn->query("SELECT requestId FROM requests
                       WHERE bookId=$bookId AND requesterId=$requesterId AND status='Pending'");

if ($book && $book['ownerId'] != $requesterId && $check->num_rows == 0) {
    $ownerId = (int)$book['ownerId'];
    $conn->query("INSERT INTO requests (bookId, requesterId, ownerId)
                  VALUES ($bookId, $requesterId, $ownerId)");
}

header("Location: books.php");
?>