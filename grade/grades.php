<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\App;

$container = $app->getContainer();
$container['db'] = function () {
    $database = new Database();
    return $database->getConnection();
};

// Endpoint to fetch submissions for a specific assignment
$app->get('/submissions', function ($request, $response, $args) {
    $assignmentId = $request->getQueryParams()['assignment_id'];
    if (!$assignmentId) {
        return $response->withJson(["error" => "Assignment ID is required"], 400);
    }

    $sql = "SELECT g.id, g.assignment_id, g.student_id, g.grading_status, g.file_name, g.grade,
                   u.username as student_name,
            CASE WHEN g.file_name IS NOT NULL THEN CONCAT('/submissions/', g.file_name) ELSE NULL END AS file_url
            FROM grades g
            LEFT JOIN users u ON g.student_id = u.id
            WHERE g.assignment_id = :assignment_id";

    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':assignment_id', $assignmentId);
        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $response->withJson($submissions);
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

// Endpoint to update the grade for a specific student and assignment
$app->post('/grades', function ($request, $response, $args) {
    $data = json_decode($request->getBody(), true);

    $sql = "UPDATE grades SET grade = :grade, grading_status = :grading_status 
            WHERE assignment_id = :assignment_id AND student_id = :student_id";

    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':grade', $data['grade']);
        $stmt->bindParam(':grading_status', $data['grading_status']);
        $stmt->bindParam(':assignment_id', $data['assignment_id']);
        $stmt->bindParam(':student_id', $data['student_id']);
        $stmt->execute();
        
        return $response->withJson(["message" => "Grade was updated."]);
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

// Endpoint to download a file associated with a specific assignment
$app->get('/downloadfile/{file_name}', function ($request, $response, $args) {
    $fileName = $args['file_name'];
    $filePath = '../uploads/' . $fileName;

    if (file_exists($filePath)) {
        return $response->withHeader('Content-Description', 'File Transfer')
                        ->withHeader('Content-Type', 'application/octet-stream')
                        ->withHeader('Content-Disposition', 'attachment; filename="'.basename($filePath).'"')
                        ->withHeader('Expires', '0')
                        ->withHeader('Cache-Control', 'must-revalidate')
                        ->withHeader('Pragma', 'public')
                        ->withHeader('Content-Length', filesize($filePath))
                        ->write(file_get_contents($filePath));
    } else {
        return $response->withJson(["error" => "File not found"], 404);
    }
});

$app->get('/submissionsstudent', function ($request, $response, $args) {
    $userId = $request->getQueryParam('userId');
    if (!$userId) {
        return $response->withJson(["error" => "User ID is required"], 400);
    }

    $sql = "SELECT g.id, a.name AS assignment_name, g.grading_status, g.grade
            FROM grades g
            JOIN assignments a ON g.assignment_id = a.id
            WHERE g.student_id = :userId";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $response->withJson($submissions);
    } catch (PDOException $e) {
        return $response->withJson(["error" => "Database error: " . $e->getMessage()], 500);
    }
});

$app->run();
