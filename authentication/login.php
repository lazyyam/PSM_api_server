<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

use \Firebase\JWT\JWT;

require '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\App;

$container = $app->getContainer();
$container['db'] = function () {
    $database = new Database();
    return $database->getConnection();
};

// Route for user login
$app->post('/login', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $username = $data['username'];
    $password = $data['password'];

    // Validate required fields
    if (!$username || !$password) {
        return $response->withJson(["success" => false, "message" => "All fields are required."])->withStatus(400);
    }

    // Check if user exists
    $sql = "SELECT * FROM users WHERE username = :username";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $secret_key = "your_secret_key";
            $payload = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
            ];
            $jwt = JWT::encode($payload, $secret_key, 'HS256');
            return $response->withJson(["success" => true, "token" => $jwt]);
        } else {
            return $response->withJson(["success" => false, "message" => "Invalid username or password."])->withStatus(400);
        }
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

$app->run();
?>