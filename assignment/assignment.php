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
    $sql = "SELECT id, name, set_time, due_date, description, file_name, 
            CASE WHEN file_name IS NOT NULL THEN CONCAT('/downloadfile/', id) ELSE NULL END AS file_url,
            TIMESTAMPDIFF(SECOND, NOW(), due_date) AS remaining_time_seconds
            FROM assignments";
    try {
        $stmt = $this->db->query($sql);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging: Log the fetched assignments
        file_put_contents('php://stderr', print_r($assignments, true));

        return $response->withJson($assignments);
    } catch (PDOException $e) {
        // Debugging: Log the error
        file_put_contents('php://stderr', "Database error: " . $e->getMessage());
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

// Download file
$app->get('/downloadfile/{id}', function ($request, $response, $args) {
    $id = $args['id'];

    // Fetch the file and its name from the database
    $sql = "SELECT file, file_name FROM assignments WHERE id = :id";
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

// Create new assignment
$app->post('/createassignment', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $uploadedFile = $request->getUploadedFiles()['file'] ?? null; // Fetch uploaded file, if any

    // Validate input data
    if (empty($data['name']) || empty($data['set_time']) || empty($data['due_date']) || empty($data['description']) ) {
        return $response->withJson(["error" => "Missing required fields"], 400);
    }

    $fileContent = null;
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

    $sql = "INSERT INTO assignments (name, set_time, due_date, description, file_name, file) 
    VALUES (:name, :set_time, :due_date, :description, :file_name, :file)";

    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':set_time', $data['set_time']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':file_name', $fileName);

        if ($fileName !== null) {
            $stmt->bindParam(':file_name', $fileName);
        } else {
            $stmt->bindValue(':file_name', null, PDO::PARAM_NULL);
        }
        
        if ($fileContent !== null) {
            $stmt->bindParam(':file', $fileContent, PDO::PARAM_LOB); // Store file content as BLOB
        } else {
            // If no file is uploaded, bind a NULL value
            $stmt->bindValue(':file', null, PDO::PARAM_LOB);
        }

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
$app->post('/updateassignment/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $parsedBody = $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();
    $uploadedFile = $uploadedFiles['file'] ?? null;

    // Validate input data
    if (empty($parsedBody['name']) || empty($parsedBody['set_time']) || empty($parsedBody['due_date']) || empty($parsedBody['description']) ) {
        return $response->withJson(["error" => "Missing required fields"], 400);
    }

    // Fetch existing assignment data
    $sql = "SELECT file_name FROM assignments WHERE id = :id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $existingAssignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingAssignment) {
            return $response->withJson(["error" => "Assignment not found"], 404);
        }

        $fileContent = null;
        $fileName = $existingAssignment['file_name'];

        if ($uploadedFile) {
            $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            $fileMimeType = $uploadedFile->getClientMediaType();
            if (in_array($fileMimeType, $allowedMimeTypes)) {
                $fileContent = $uploadedFile->getStream()->getContents();
                $fileName = $uploadedFile->getClientFilename();
            } else {
                return $response->withJson(["error" => "Invalid file type"], 400);
            }
        }

        $sql = "UPDATE assignments 
                SET name = :name, set_time = :set_time, due_date = :due_date, description = :description, file_name = :file_name, file = :file
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $parsedBody['name']);
        $stmt->bindParam(':set_time', $parsedBody['set_time']);
        $stmt->bindParam(':due_date', $parsedBody['due_date']);
        $stmt->bindParam(':description', $parsedBody['description']);
        $stmt->bindParam(':file_name', $fileName);

        if ($fileContent !== null) {
            $stmt->bindParam(':file', $fileContent, PDO::PARAM_LOB);
        } else {
            $stmt->bindValue(':file', null, PDO::PARAM_LOB);
        }

        if ($stmt->execute()) {
            return $response->withJson(["message" => "Assignment updated successfully", "data" => $parsedBody]);
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
