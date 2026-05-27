<?php
require_once 'Database.php';
require_once 'SessionHelper.php';
require_once 'ImageUploader.php';
require_once 'ReportView.php';

class LostReport {
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

    private function generateTicketNumber(): string {
        $result = $this->conn->query('SELECT MAX(LostID) AS maxId FROM lost');
        $row    = $result ? $result->fetch_assoc() : null;
        $nextId = ($row['maxId'] ?? 0) + 1;
        return 'NU-' . str_pad(1000 + $nextId, 4, '0', STR_PAD_LEFT);
    }

    public function submit(string $studentNumber, array $data, ?string $imagePath): array {
        if ($studentNumber === '') {
            return ['status' => 'error', 'message' => 'Please log in before submitting a report.'];
        }

        if (empty($data['Location']) || empty($data['DateLost']) || empty($data['Category']) || empty($data['Description'])) {
            return ['status' => 'error', 'message' => 'Please fill in all required fields.'];
        }

        if (!$this->validateStudent($studentNumber)) {
            return ['status' => 'error', 'message' => 'Student number not found in student records.'];
        }

        // Self-match check — block if same student has a found report matching this lost report
        $selfCheck = $this->conn->prepare("
            SELECT COUNT(*) AS cnt FROM found
            WHERE StudentNumber = ?
            AND Category = ?
            AND DATEDIFF(DateFound, ?) BETWEEN -3 AND 30
            AND Status = 'Unclaimed'
        ");
        $selfCheck->bind_param('sss', $studentNumber, $data['Category'], $data['DateLost']);
        $selfCheck->execute();
        $selfRow = $selfCheck->get_result()->fetch_assoc();
        if ((int)($selfRow['cnt'] ?? 0) > 0) {
            return [
                'status'  => 'error',
                'message' => 'Invalid submission. You already have a found report that matches this lost item. You cannot submit both sides of a match.'
            ];
        }

        $ticketNumber = $this->generateTicketNumber();

        $stmt = $this->conn->prepare(
            'INSERT INTO lost (TicketNumber, StudentNumber, Location, DateLost, Category, Description, Image) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssss',
            $ticketNumber,
            $studentNumber,
            $data['Location'],
            $data['DateLost'],
            $data['Category'],
            $data['Description'],
            $imagePath
        );

        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Unable to save the lost item report. Please try again later.'];
        }

        return [
            'status'  => 'success',
            'message' => 'Your lost item report has been successfully submitted. Ticket Number: ' . $ticketNumber
        ];
    }
}

// Entry point
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    SessionHelper::requireLogin();

    $report    = new LostReport();
    $imagePath = ImageUploader::upload('ItemImage', 'lost');
    $result    = $report->submit(
        SessionHelper::get('StudentNumber', ''),
        [
            'Location'    => trim($_POST['Location']    ?? ''),
            'DateLost'    => trim($_POST['DateLost']    ?? ''),
            'Category'    => trim($_POST['Category']    ?? ''),
            'Description' => trim($_POST['Description'] ?? ''),
        ],
        $imagePath
    );

    echo json_encode($result);
    exit;
}