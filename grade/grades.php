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

// Get all submissions for a specific assignment
$app->get('/submissions', function ($request, $response, $args) {
    $assignmentId = $request->getParam('assignmentId');
    $sql = "SELECT g.student_id, u.username as studentName, g.submission_file_url as file_url, g.grade, g.submittedtime
            FROM grades g
            JOIN users u ON g.student_id = u.id
            WHERE g.assignment_id = :assignmentId";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':assignmentId', $assignmentId);
        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $response->withJson($submissions);
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});


// Submit or update grade for a specific student submission
$app->post('/grades', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $assignmentId = $data['assignment_id'];
    $studentId = $data['student_id'];
    $grade = $data['grade'];

    $sql = "UPDATE grades SET grade = :grade, submittedtime = NOW(), grading_status=1
            WHERE assignment_id = :assignment_id AND student_id = :student_id";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':assignment_id', $assignmentId);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->bindParam(':grade', $grade);
        $stmt->execute();
        return $response->withJson(["success" => true, "message" => "Grade submitted successfully"]);
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

// Get assignments for a specific user
$app->get('/assignments', function ($request, $response, $args) {
    $userId = $request->getParam('userId');
    $sql = "SELECT a.id, a.name, 
                   CASE 
                       WHEN g.grading_status = 0 THEN 'Submitted'
                       WHEN g.grading_status = 1 THEN 'Graded'
                       ELSE 'Pending'
                   END as status
            FROM assignments a
            LEFT JOIN grades g ON a.id = g.assignment_id AND g.student_id = :userId
            WHERE g.student_id = :userId OR g.student_id IS NULL
            ORDER BY CASE 
                        WHEN g.grading_status = 0 THEN 1
                        WHEN g.grading_status = 1 THEN 2
                        ELSE 3
                     END ASC";
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $response->withJson($assignments);
    } catch (PDOException $e) {
        return $response->withJson(["success" => false, "message" => $e->getMessage()])->withStatus(500);
    }
});

$app->run();
?>
