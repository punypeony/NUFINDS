<?php
require_once 'Database.php';

class ReportTracker {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function getStudentInfo(string $studentNumber): ?array {
        $stmt = $this->conn->prepare(
            'SELECT StudentEmail, CollegeDepartment FROM studentinfo WHERE StudentNumber = ?'
        );
        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result && $result->num_rows === 1) ? $result->fetch_assoc() : null;
    }

    public function getReports(string $studentNumber): array {
        $sql = "
            SELECT LostID, TicketNumber, Category, DateLost AS ReportDate, 'Lost' AS ReportType, 'Submitted' AS Status
            FROM lost WHERE StudentNumber = ?
            UNION ALL
            SELECT NULL AS LostID, NULL AS TicketNumber, Category, DateFound AS ReportDate, 'Found' AS ReportType, Status
            FROM found WHERE StudentNumber = ?
            ORDER BY ReportDate DESC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $studentNumber, $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        return $reports;
    }

    public function cancelLostReport(int $lostId, string $studentNumber): array {
        if ($lostId <= 0 || $studentNumber === '') {
            return ['status' => 'error', 'message' => 'Unable to cancel the lost report.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM lost WHERE LostID = ? AND StudentNumber = ?');
        $stmt->bind_param('is', $lostId, $studentNumber);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return ['status' => 'success', 'message' => 'Lost report canceled successfully.'];
        }

        return ['status' => 'error', 'message' => 'No matching lost report found or it has already been removed.'];
    }
}