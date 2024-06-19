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

// Route to get user profile
$app->get('/profile/{id}', function ($request, $response, $args) {
    $id = $args['id'];

    $sql = "SELECT * FROM users WHERE id = :id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $response->withJson(["success" => true, "user" => $user]);
        } else {
            return $response->withJson(["success" => false, "message" => "User not found."])->withStatus(404);
        }
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

// Route to update user profile
$app->put('/updateProfile/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $data = $request->getParsedBody();

    // Initialize an array to store the SET clauses for the SQL query
    $setClauses = [];

    // Check which fields are present in the request and add them to the SET clauses
    if (!empty($data['username'])) {
        $setClauses[] = 'username = :username';
    }
    if (!empty($data['fullname'])) {
        $setClauses[] = 'fullname = :fullname';
    }
    if (!empty($data['address'])) {
        $setClauses[] = 'address = :address';
    }
    if (!empty($data['city'])) {
        $setClauses[] = 'city = :city';
    }
    if (!empty($data['country'])) {
        $setClauses[] = 'country = :country';
    }
    if (!empty($data['postcode'])) {
        $setClauses[] = 'postcode = :postcode';
    }
    if (!empty($data['about_me'])) {
        $setClauses[] = 'about_me = :about_me';
    }

    // Validate if any fields were provided for update
    if (empty($setClauses)) {
        return $response->withJson(["success" => false, "message" => "No fields provided for update."])->withStatus(400);
    }

    // Construct the SET clause for the SQL query
    $setClause = implode(', ', $setClauses);

    // Construct the SQL query
    $sql = "UPDATE users SET $setClause WHERE id = :id";

    try {
        $stmt = $this->db->prepare($sql);

        // Bind parameters for fields that are present in the request
        if (!empty($data['username'])) {
            $stmt->bindParam(':username', $data['username']);
        }
        if (!empty($data['fullname'])) {
            $stmt->bindParam(':fullname', $data['fullname']);
        }
        if (!empty($data['address'])) {
            $stmt->bindParam(':address', $data['address']);
        }
        if (!empty($data['city'])) {
            $stmt->bindParam(':city', $data['city']);
        }
        if (!empty($data['country'])) {
            $stmt->bindParam(':country', $data['country']);
        }
        if (!empty($data['postcode'])) {
            $stmt->bindParam(':postcode', $data['postcode']);
        }
        if (!empty($data['about_me'])) {
            $stmt->bindParam(':about_me', $data['about_me']);
        }

        // Bind the user ID parameter
        $stmt->bindParam(':id', $id);

        // Execute the query
        $stmt->execute();

        return $response->withJson(["success" => true, "message" => "Profile updated successfully."]);
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

// Route to delete user profile
$app->delete('/profile/{id}', function ($request, $response, $args) {
    $id = $args['id'];

    $sql = "DELETE FROM users WHERE id = :id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $response->withJson(["success" => true, "message" => "Profile deleted successfully."]);
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

$app->run();
?>