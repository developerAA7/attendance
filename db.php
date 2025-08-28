<?php
$host = "127.0.0.1";        // better than "localhost" when using custom port
$user = "root";             // default username in XAMPP
$password = "";             // default password is empty in XAMPP
$database = "attendance_db"; // your database name
$port = 3308;               // your MySQL port

$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
