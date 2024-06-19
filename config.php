<?php
$host = 'localhost';
$db_user = 'root';  // Adjust this according to your MySQL settings
$db_password = '';  // Adjust this according to your MySQL settings
$db_name = 'psm_database';

// Create connection
$conn = new mysqli($host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
