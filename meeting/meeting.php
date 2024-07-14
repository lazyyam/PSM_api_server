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

//Get all meetings
$app->get('/meetings', function ($request, $response, $args) {
    $sql = "SELECT * FROM meetings";
    try {
        $stmt = $this->db->query($sql);
        $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $response->withJson($meetings);
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

//Add new meeting
$app->post('/meetings', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $user = $request->getAttribute('user');

    $sql = "INSERT INTO meetings (Mtitle, Mdate, Mduration, Mlocation, Mdescription) VALUES (:Mtitle, :Mdate, :Mduration, :Mlocation, :Mdescription)";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':Mtitle', $data['Mtitle']);
        $stmt->bindParam(':Mdate', $data['Mdate']);
        $stmt->bindParam(':Mduration', $data['Mduration']);
        $stmt->bindParam(':Mlocation', $data['Mlocation']);
        $stmt->bindParam(':Mdescription', $data['Mdescription']);
        $stmt->execute();
        return $response->withJson(["success" => true, "message" => "Meeting added successfully"]);
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

//Edit meeting
$app->put('/meetings/{id}', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $id = $args['id'];

    $sql = "UPDATE meetings SET Mtitle = :Mtitle, Mdate = :Mdate, Mduration = :Mduration, Mlocation = :Mlocation, Mdescription = :Mdescription WHERE id = :id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':Mtitle', $data['Mtitle']);
        $stmt->bindParam(':Mdate', $data['Mdate']);
        $stmt->bindParam(':Mduration', $data['Mduration']);
        $stmt->bindParam(':Mlocation', $data['Mlocation']);
        $stmt->bindParam(':Mdescription', $data['Mdescription']);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $response->withJson(["success" => true, "message" => "Meeting updated successfully"]);
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

//Delete meeting
$app->delete('/meetings/{id}', function ($request, $response, $args) {
    $id = $args['id'];

    $sql = "DELETE FROM meetings WHERE id = :id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $response->withJson(["success" => true, "message" => "Meeting deleted successfully"]);
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

$app->run();
?>