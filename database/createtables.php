<?php
include 'config.php';

// Create an instance of the Database class
$database = new Database();
$conn = $database->getConnection();

// Check connection
if ($conn === false) {
    die("Connection failed: Unable to connect to the database.");
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
    role VARCHAR(20) NOT NULL DEFAULT 'student', -- Added role column with default value 'student'
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default lecturer user
INSERT INTO users (username, email, password, role)
VALUES ('lecturer1', 'lecturer1@gmail.com', '".password_hash('lecturer1', PASSWORD_DEFAULT)."', 'lecturer');
";

try {
    // Execute SQL query
    $conn->exec($sql);
    echo "Tables created successfully";
} catch(PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}

// Close the connection
$conn = null;
?>