<?php
include 'config.php';

// Connect to the psm_database
$conn = new mysqli($host, $db_user, $db_password, 'psm_database');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create tables
$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    email VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    postcode VARCHAR(20),
    about_me TEXT,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

if ($conn->query($sql) === TRUE) {
    echo "Tables created successfully";
} else {
    echo "Error creating tables: " . $conn->error;
}

$conn->close();
?>