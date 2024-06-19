<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

require '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\App;

// Route for user login
$app->post('/login', function ($request, $response, $args) use ($conn) {
    $data = $request->getParsedBody();
    
    $username = $data['username'];
    $password = $data['password'];

    // Fetch user from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // User authentication successful
            $role = ($username === 'lecturer1') ? 'LecturerDashboard' : 'StudentDashboard';
            return $response->withJson(["message" => "Login successful", "role" => $role]);
        } else {
            return $response->withJson(["error" => "Invalid username or password"])->withStatus(401);
        }
    } else {
        return $response->withJson(["error" => "User not found"])->withStatus(404);
    }
});

// Other routes can be added here

$app->run();
?>