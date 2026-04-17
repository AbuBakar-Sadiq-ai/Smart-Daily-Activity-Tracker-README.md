<?php
$conn = new mysqli("localhost", "root", "", "time_table_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
