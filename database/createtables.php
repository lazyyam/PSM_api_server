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

-- Insert the default lecturer user if the username 'lecturer1' does not exist
INSERT INTO users (username, email, password, role)
SELECT 'lecturer1', 'lecturer1@gmail.com', '".password_hash('lecturer1', PASSWORD_DEFAULT)."', 'lecturer'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'lecturer1');

CREATE TABLE IF NOT EXISTS meetings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Mtitle VARCHAR(255) NOT NULL,
    Mdate DATETIME NOT NULL,
    Mduration VARCHAR(50) NOT NULL,
    Mlocation VARCHAR(255) NOT NULL,
    Mdescription TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create the assignments table
CREATE TABLE IF NOT EXISTS assignments (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    set_time VARCHAR(255) NOT NULL,
    due_date VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    file_name VARCHAR(255),
    file LONGBLOB
);

-- newest grades table
CREATE TABLE IF NOT EXISTS grades (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED ,
    student_id INT UNSIGNED,
    grading_status BOOLEAN NOT NULL DEFAULT 0,
    file_name VARCHAR(255),
    file LONGBLOB,
    grade VARCHAR(255),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

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