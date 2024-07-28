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

// Get all submissions for a specific student
$app->get('/getsubmissions/{student_id}', function ($request, $response, $args) {
    $studentId = $args['student_id'];
    $sql = "SELECT s.id, s.assignment_id, s.student_id, s.file_name, 
            CASE WHEN s.file_name IS NOT NULL THEN CONCAT('/downloadfile/', s.assignment_id) ELSE NULL END AS file_url
            FROM grades s
            JOIN assignments a ON s.assignment_id = a.id
            WHERE s.student_id = :student_id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $response->withJson($submissions);
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});


$app->get('/downloadsubmission/{id}', function ($request, $response, $args) {
    $id = $args['id'];

    // Fetch the file and its name from the database
    $sql = "SELECT file, file_name FROM grades WHERE id = :id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $fileContent = $result['file'];
            $fileName = $result['file_name'];

            if (empty($fileName)) {
                $fileName = 'default_filename'; // Fallback file name
            }

            $response = $response
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->write($fileContent);

            return $response;
        } else {
            return $response->withJson(["error" => "File not found"], 404);
        }
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

// Submit a new submission
$app->post('/submitassignment', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $uploadedFile = $request->getUploadedFiles()['file'] ?? null; // Fetch uploaded file, if any

    // Validate input data
    if (empty($data['assignment_id']) || empty($data['student_id'])) {
        return $response->withJson(["error" => "Missing required fields"], 400);
    }

    $fileContent = null;
    $fileName = null;
    if ($uploadedFile) {
        // Check if the file's MIME type is allowed
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $fileMimeType = $uploadedFile->getClientMediaType();
        if (in_array($fileMimeType, $allowedMimeTypes)) {
            $fileContent = $uploadedFile->getStream()->getContents(); // Read file content
            $fileName = $uploadedFile->getClientFilename(); // Get original file name
        } else {
            return $response->withJson(["error" => "Invalid file type"], 400);
        }
    }

    $sql = "INSERT INTO grades (assignment_id, student_id, file_name, file) 
            VALUES (:assignment_id, :student_id, :file_name, :file)";

    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':assignment_id', $data['assignment_id']);
        $stmt->bindParam(':student_id', $data['student_id']);
        $stmt->bindParam(':file_name', $fileName);

        if ($fileContent !== null) {
            $stmt->bindParam(':file', $fileContent, PDO::PARAM_LOB); // Store file content as BLOB
        } else {
            // If no file is uploaded, bind a NULL value
            $stmt->bindValue(':file', null, PDO::PARAM_LOB);
        }

        if ($stmt->execute()) {
            $data['id'] = $this->db->lastInsertId();
            return $response->withJson(["message" => "Submission created successfully", "data" => $data]);
        } else {
            return $response->withJson(["error" => "Error creating submission"], 500);
        }
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

// Delete a submission
$app->delete('/deletesubmission/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $sql = "DELETE FROM grades WHERE id = :id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return $response->withJson(["message" => "Submission deleted successfully", "id" => $id]);
        } else {
            return $response->withJson(["error" => "Error deleting submission"], 500);
        }
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

$app->run();
?>