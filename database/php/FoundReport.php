<?php
require_once 'Database.php';
require_once 'SessionHelper.php';
require_once 'ImageUploader.php';
require_once 'ReportView.php';

class FoundReport {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    private function validateStudent(string $studentNumber): bool {
        $stmt = $this->conn->prepare('SELECT StudentNumber FROM studentinfo WHERE StudentNumber = ?');
        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->num_rows === 1;
    }

    public function submit(string $studentNumber, array $data, ?string $imagePath): array {
        if ($studentNumber === '') {
            return ['status' => 'error', 'message' => 'Please log in before submitting a report.'];
        }

        if (empty($data['Location']) || empty($data['DateFound']) || empty($data['Category']) || empty($data['Description'])) {
            return ['status' => 'error', 'message' => 'Please fill in all required fields.'];
        }

        if (!$this->validateStudent($studentNumber)) {
            return ['status' => 'error', 'message' => 'Student number not found in student records.'];
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO found (StudentNumber, Location, DateFound, Category, Description, Image) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssss',
            $studentNumber,
            $data['Location'],
            $data['DateFound'],
            $data['Category'],
            $data['Description'],
            $imagePath
        );

        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Unable to save the found item report. Please try again later.'];
        }

        return ['status' => 'success', 'message' => 'Your found item report has been successfully submitted.'];
    }
}

// Entry point
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    SessionHelper::requireLogin();

    $report    = new FoundReport();
    $imagePath = ImageUploader::upload('ItemImage', 'found');
    $result    = $report->submit(
        SessionHelper::get('StudentNumber', ''),
        [
            'Location'    => trim($_POST['Location']    ?? ''),
            'DateFound'   => trim($_POST['DateFound']   ?? ''),
            'Category'    => trim($_POST['Category']    ?? ''),
            'Description' => trim($_POST['Description'] ?? ''),
        ],
        $imagePath
    );

    echo json_encode($result);
    exit;
}