<?php

$host = "localhost";
$db_user = "root";
$db_password = "";

try {
    $conn = new PDO("mysql:host=$host", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS psm_database";
    $conn->exec($sql);
    echo "Database created successfully";
} catch(PDOException $e) {
    echo "Error creating database: " . $e->getMessage();
}

$conn = null;
?>