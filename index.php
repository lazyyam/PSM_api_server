<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require '../vendor/autoload.php';
require_once '../config.php';
require '../middleware/authMiddleware.php';  // Include the middleware

use \Firebase\JWT\JWT;

$app = new \Slim\App;

// Secret key for encoding JWT tokens
$secretKey = 'your_secret_key';

// Route for user login
$app->post('/login', function ($request, $response, $args) use ($conn, $secretKey) {
    $data = $request->getParsedBody();
    
    $username = $data['username'];
    $password = $data['password'];

    // Validate required fields
    if (!$username || !$password) {
        return $response->withJson(["error" => "Username and password are required."])->withStatus(400);
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $payload = [
            'iss' => "http://localhost", // Issuer
            'iat' => time(), // Issued at
            'exp' => time() + (60 * 60), // Expiration time (1 hour)
            'uid' => $user['id'], // User ID
            'username' => $user['username'], // Username
            'role' => $user['role'] // User role
        ];

        $jwt = JWT::encode($payload, $secretKey);

        return $response->withJson(["message" => "Login successful!", "token" => $jwt]);
    } else {
        return $response->withJson(["error" => "Invalid username or password."])->withStatus(401);
    }
});

// Protected route example
$app->get('/protected', 'authenticate', function ($request, $response, $args) {
    $user = $request->getAttribute('user');
    return $response->withJson(["message" => "Welcome, " . $user->username . "!"]);
});

// Other routes can be added here

$app->run();
?>