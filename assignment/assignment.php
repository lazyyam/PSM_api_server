<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

use Slim\Http\UploadedFile;

require '../vendor/autoload.php';
require_once '../config.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$app = new \Slim\App;

$container = $app->getContainer();
$container['db'] = function () {
    $database = new Database();
    return $database->getConnection();
};

// Get all assignments
$app->get('/getassignmentlist', function ($request, $response, $args) {
    $sql = "SELECT * FROM assignments";
    try {
        $stmt = $this->db->query($sql);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $response->withJson($assignments);
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

// Create new assignment
$app->post('/createassignment', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $uploadedFile = $request->getUploadedFiles()['file'] ?? null; // Fetch uploaded file, if any

    // Move the uploaded file and get its new path
    $file = null;
    if ($uploadedFile) {
        $directory = __DIR__ . '/uploads'; // Specify your upload directory
        $file = moveUploadedFile($directory, $uploadedFile);
    }

    $sql = "INSERT INTO assignments (name, set_time, due_date, description, remaining_time, file) 
            VALUES (:name, :set_time, :due_date, :description, :remaining_time, :file)";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':set_time', $data['set_time']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':remaining_time', $data['remaining_time']);
        $stmt->bindParam(':file', $file); // Use the file path/name here

        if ($stmt->execute()) {
            $data['id'] = $this->db->lastInsertId();
            return $response->withJson(["message" => "Assignment created successfully", "data" => $data]);
        } else {
            return $response->withJson(["error" => "Error creating assignment"], 500);
        }
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

// Update assignment
$app->put('/updateassignment/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $data = $request->getParsedBody();

    // Initialize an array to store the SET clauses for the SQL query
    $setClauses = [];

    // Check which fields are present in the request and add them to the SET clauses
    if (!empty($data['name'])) {
        $setClauses[] = 'name = :name';
    }
    if (!empty($data['set_time'])) {
        $setClauses[] = 'set_time = :set_time';
    }
    if (!empty($data['due_date'])) {
        $setClauses[] = 'due_date = :due_date';
    }
    if (!empty($data['description'])) {
        $setClauses[] = 'description = :description';
    }
    if (!empty($data['remaining_time'])) {
        $setClauses[] = 'remaining_time = :remaining_time';
    }
    if (!empty($data['file'])) {
        $setClauses[] = 'file = :file';
    }

    // Validate if any fields were provided for update
    if (empty($setClauses)) {
        return $response->withJson(["error" => "No fields provided for update"], 400);
    }

    // Construct the SET clause for the SQL query
    $setClause = implode(', ', $setClauses);

    // Construct the SQL query
    $sql = "UPDATE assignments SET $setClause WHERE id = :id";

    try {
        $stmt = $this->db->prepare($sql);

        // Bind parameters for fields that are present in the request
        if (!empty($data['name'])) {
            $stmt->bindParam(':name', $data['name']);
        }
        if (!empty($data['set_time'])) {
            $stmt->bindParam(':set_time', $data['set_time']);
        }
        if (!empty($data['due_date'])) {
            $stmt->bindParam(':due_date', $data['due_date']);
        }
        if (!empty($data['description'])) {
            $stmt->bindParam(':description', $data['description']);
        }
        if (!empty($data['remaining_time'])) {
            $stmt->bindParam(':remaining_time', $data['remaining_time']);
        }
        if (!empty($data['file'])) {
            // Handle file upload - move file to desired directory and store path in database
            $directory = __DIR__ . '/uploads';
            $file = moveUploadedFile($directory, $data['file']);
            $stmt->bindParam(':file', $file);
        }

        // Bind the assignment ID parameter
        $stmt->bindParam(':id', $id);

        // Execute the query
        if ($stmt->execute()) {
            // Fetch updated assignment data
            $sqlFetch = "SELECT * FROM assignments WHERE id = :id";
            $stmtFetch = $this->db->prepare($sqlFetch);
            $stmtFetch->bindParam(':id', $id);
            $stmtFetch->execute();
            $updatedAssignment = $stmtFetch->fetch(PDO::FETCH_ASSOC);

            return $response->withJson($updatedAssignment);
        } else {
            return $response->withJson(["error" => "Error updating assignment"], 500);
        }
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

// Delete assignment
$app->delete('/deleteassignment/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $sql = "DELETE FROM assignments WHERE id = :id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return $response->withJson(["message" => "Assignment deleted successfully", "id" => $id]);
        } else {
            return $response->withJson(["error" => "Error deleting assignment"], 500);
        }
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

$app->run();

function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8));
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}
?>
