<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

require '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\App;

// Route for updating user profile
$app->put('/profile/{id}', function ($request, $response, $args) use ($conn) {
    $id = $args['id'];
    $data = $request->getParsedBody();
    
    $username = $data['username'];
    $email = $data['email'];
    $firstName = $data['firstName'];
    $lastName = $data['lastName'];
    $address = $data['address'];
    $city = $data['city'];
    $country = $data['country'];
    $postalCode = $data['postalCode'];
    $aboutMe = $data['aboutMe'];

    $sql = "UPDATE users SET username = ?, email = ?, firstName = ?, lastName = ?, address = ?, city = ?, country = ?, postalCode = ?, aboutMe = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssi", $username, $email, $firstName, $lastName, $address, $city, $country, $postalCode, $aboutMe, $id);
    if ($stmt->execute()) {
        return $response->withJson(["message" => "Profile updated successfully"]);
    } else {
        return $response->withJson(["error" => "Error updating profile: " . $stmt->error])->withStatus(500);
    }
});

$app->run();
?>