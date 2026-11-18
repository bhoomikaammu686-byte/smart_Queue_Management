
<?php

$host = "localhost";
$username = "root";
$password = "Bhoomi25";
$database = "smart_queue_db";
$port = 3307;

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");



?>

