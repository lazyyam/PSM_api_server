<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
require '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\App;

// Route to get user profile data
$app->get('/profile/{username}', function ($request, $response, $args) use ($conn) {
    $username = $args['username'];
    $stmt = $conn->prepare("SELECT username, email, fullname, address, city, country, postcode, about_me, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $response->withJson($user);
    } else {
        return $response->withJson(["error" => "User not found"])->withStatus(404);
    }
});

// Route to update user profile data
$app->post('/profile/{username}', function ($request, $response, $args) use ($conn) {
    $username = $args['username'];
    $data = $request->getParsedBody();
    
    $email = $data['email'];
    $fullname = $data['fullname'];
    $address = $data['address'];
    $city = $data['city'];
    $country = $data['country'];
    $postcode = $data['postcode'];
    $about_me = $data['about_me'];
    
    $stmt = $conn->prepare("UPDATE users SET email = ?, fullname = ?, address = ?, city = ?, country = ?, postcode = ?, about_me = ? WHERE username = ?");
    $stmt->bind_param("ssssssss", $email, $fullname, $address, $city, $country, $postcode, $about_me, $username);
    
    if ($stmt->execute()) {
        return $response->withJson(["message" => "Profile updated successfully"]);
    } else {
        return $response->withJson(["error" => "Error updating profile: " . $stmt->error])->withStatus(500);
    }
});

$app->run();
?>