<?php
require_once __DIR__ . '/Database.php';

class ReportService {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function submit(string $type, string $studentNumber, array $data, ?string $imagePath, bool $forceSubmit = false): array {
        $type = strtolower(trim($type));
        if ($type !== 'found' && $type !== 'lost') {
            return ['status' => 'error', 'message' => 'Invalid report type.'];
        }

        if ($studentNumber === '') {
            return ['status' => 'error', 'message' => 'Please log in before submitting a report.'];
        }

        $requiredKeys = ['Location', 'Date', 'Category', 'Description'];
        foreach ($requiredKeys as $key) {
            if (empty($data[$key])) {
                return ['status' => 'error', 'message' => 'Please fill in all required fields.'];
            }
        }

        if (!$this->validateStudent($studentNumber)) {
            return ['status' => 'error', 'message' => 'Student number not found in student records.'];
        }

        $dateError = $this->validateReportDate($data['Date']);
        if ($dateError !== null) {
            return ['status' => 'error', 'message' => $dateError];
        }

        if (!$forceSubmit && $this->isSelfMatch($studentNumber, $data['Category'], $data['Date'], $type)) {
            return [
                'status' => 'warning',
                'message' => $type === 'found'
                    ? 'You already have a lost report with the same category. Are you sure this is a different item?'
                    : 'You already have a found report with the same category. Are you sure this is a different item?'
            ];
        }

        if ($type === 'found') {
            return $this->saveFoundReport($studentNumber, $data, $imagePath);
        }

        return $this->saveLostReport($studentNumber, $data, $imagePath);
    }

    private function validateReportDate(string $date): ?string {
        $date = trim($date);
        if ($date === '') {
            return 'Please select a valid date.';
        }

        $today = date('Y-m-d');
        $min   = date('Y-m-d', strtotime('-1 year'));

        if ($date > $today) {
            return 'Please select a date on or before today.';
        }

        if ($date < $min) {
            return 'Please select a date within the past year.';
        }

        return null;
    }

    private function validateStudent(string $studentNumber): bool {
        $stmt = $this->conn->prepare('SELECT StudentNumber FROM studentinfo WHERE StudentNumber = ?');
        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->num_rows === 1;
    }

    private function isSelfMatch(string $studentNumber, string $category, string $date, string $type): bool {
        if ($type === 'found') {
            $stmt = $this->conn->prepare(
                'SELECT COUNT(*) AS cnt FROM lost WHERE StudentNumber = ? AND Category = ? AND DATEDIFF(?, DateLost) BETWEEN -3 AND 30'
            );
            $stmt->bind_param('sss', $studentNumber, $category, $date);
        } else {
            $stmt = $this->conn->prepare(
                'SELECT COUNT(*) AS cnt FROM found WHERE StudentNumber = ? AND Category = ? AND DATEDIFF(DateFound, ?) BETWEEN -3 AND 30 AND Status = "Unclaimed"'
            );
            $stmt->bind_param('sss', $studentNumber, $category, $date);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int)($row['cnt'] ?? 0) > 0;
    }

    private function saveFoundReport(string $studentNumber, array $data, ?string $imagePath): array {
        $stmt = $this->conn->prepare(
            'INSERT INTO found (StudentNumber, Location, DateFound, Category, Description, Image) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssss',
            $studentNumber,
            $data['Location'],
            $data['Date'],
            $data['Category'],
            $data['Description'],
            $imagePath
        );

        if (!$stmt->execute()) {
            return ['status' => 'error', 'message' => 'Unable to save the found item report. Please try again later.'];
        }

        return ['status' => 'success', 'message' => 'Your found item report has been successfully submitted.'];
    }

    private function saveLostReport(string $studentNumber, array $data, ?string $imagePath): array {
        $ticketNumber = $this->generateTicketNumber();

        $stmt = $this->conn->prepare(
            'INSERT INTO lost (TicketNumber, StudentNumber, Location, DateLost, Category, Description, Image) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssss',
            $ticketNumber,
            $studentNumber,
            $data['Location'],
            $data['Date'],
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

    private function generateTicketNumber(): string {
        $result = $this->conn->query('SELECT MAX(LostID) AS maxId FROM lost');
        $row    = $result ? $result->fetch_assoc() : null;
        $nextId = ($row['maxId'] ?? 0) + 1;
        return 'NU-' . str_pad(1000 + $nextId, 4, '0', STR_PAD_LEFT);
    }
}
