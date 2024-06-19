<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\App;

// Route for user registration
$app->post('/register', function ($request, $response, $args) use ($conn) {
    $data = $request->getParsedBody();
    
    $username = $data['username'];
    $email = $data['email'];
    $password = $data['password'];
    $confirmPassword = $data['confirmPassword'];
    $role = 'student';

    // Validate required fields
    if (!$username || !$email || !$password || !$confirmPassword) {
        return $response->withJson(["error" => "All fields are required."])->withStatus(400);
    }

    // Check if passwords match
    if ($password !== $confirmPassword) {
        return $response->withJson(["error" => "Passwords do not match."])->withStatus(400);
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    // Execute the statement
    if ($stmt->execute()) {
        return $response->withJson(["message" => "Registration successful!"]);
    } else {
        return $response->withJson(["error" => "Error: " . $stmt->error])->withStatus(500);
    }
});

// Other routes can be added here

$app->run();
?>