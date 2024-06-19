<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require '../vendor/autoload.php';
require_once '../config.php';
use \Firebase\JWT\JWT;

$app = new \Slim\App;

$container = $app->getContainer();
$container['db'] = function () {
    $database = new Database();
    return $database->getConnection();
};

// Route for user registration
$app->post('/register', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    
    $username = $data['username'];
    $email = $data['email'];
    $password = $data['password'];
    $confirmPassword = $data['confirmPassword'];

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

    // Prepare and execute the statement
    $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);

        $stmt->execute();

        // Generate JWT token
       // Generate JWT token
        $secret_key = "your_secret_key";
        $payload = array(
            "username" => $username,
            "email" => $email
        );
        $jwt = JWT::encode($payload, $secret_key, 'HS256');

        return $response->withJson(["status" => "success", "token" => $jwt, "data" => ["newUser" => ["id" => $this->db->lastInsertId()]]]);
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Error: " . $e->getMessage()])->withStatus(500);
    }
});

// Other routes can be added here

$app->run();
?>