<?php
require_once __DIR__ . '/Database.php';

class ReportTracker {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function getStudentInfo(string $studentNumber): ?array {
        $studentNumber = trim($studentNumber);
        if ($studentNumber === '') {
            return null;
        }

        $stmt = $this->conn->prepare(
            'SELECT StudentEmail, CollegeDepartment FROM studentinfo WHERE StudentNumber = ?'
        );
        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        return ($result && $result->num_rows === 1) ? $result->fetch_assoc() : null;
    }

    public function getReports(string $studentNumber): array {
        $studentNumber = trim($studentNumber);
        if ($studentNumber === '') {
            return [];
        }

        $reports = array_merge(
            $this->fetchLostReports($studentNumber),
            $this->fetchFoundReports($studentNumber)
        );

        usort($reports, static function (array $a, array $b): int {
            return strtotime($b['ReportDate']) <=> strtotime($a['ReportDate']);
        });

        return $reports;
    }

    private function fetchLostReports(string $studentNumber): array {
        $stmt = $this->conn->prepare(
            "SELECT LostID, TicketNumber, Category, DateLost AS ReportDate,
                    'Lost' AS ReportType, 'Submitted' AS Status
             FROM lost
             WHERE StudentNumber = ?
             ORDER BY DateLost DESC"
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function fetchFoundReports(string $studentNumber): array {
        $stmt = $this->conn->prepare(
            "SELECT FoundID, Category, DateFound AS ReportDate,
                    'Found' AS ReportType, Status
             FROM found
             WHERE StudentNumber = ?
             ORDER BY DateFound DESC"
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['LostID']       = null;
                $row['TicketNumber'] = null;
                $rows[]              = $row;
            }
        }

        return $rows;
    }

    public function cancelLostReport(int $lostId, string $studentNumber): array {
        $studentNumber = trim($studentNumber);
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

    public function isSelfMatch(string $studentNumber, string $category, string $date, string $type): bool {
        if ($type === 'Lost') {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) AS cnt FROM found
                 WHERE StudentNumber = ?
                 AND Category = ?
                 AND DATEDIFF(DateFound, ?) BETWEEN -3 AND 30
                 AND Status = 'Unclaimed'"
            );
            $stmt->bind_param('sss', $studentNumber, $category, $date);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) AS cnt FROM lost
                 WHERE StudentNumber = ?
                 AND Category = ?
                 AND DATEDIFF(?, DateLost) BETWEEN -3 AND 30"
            );
            $stmt->bind_param('sss', $studentNumber, $category, $date);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return (int)($row['cnt'] ?? 0) > 0;
    }
}
