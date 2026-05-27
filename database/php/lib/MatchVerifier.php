<?php
require_once __DIR__ . '/Database.php';

class MatchVerifier {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function getPendingMatches(): array {
        $sql = "
            SELECT
                l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost, l.Category, l.Description,
                f.FoundID, f.StudentNumber AS FoundBy, f.Location AS FoundLocation, f.DateFound, f.Status
            FROM lost l
            LEFT JOIN found f ON
                l.Category = f.Category
                AND DATEDIFF(f.DateFound, l.DateLost) BETWEEN -3 AND 30
                AND f.Status = 'Unclaimed'
            WHERE l.LostID NOT IN (
                SELECT OriginalReportID FROM history WHERE ReportType = 'Lost'
            )
            ORDER BY l.DateLost DESC
        ";
        $result  = $this->conn->query($sql);
        $matches = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $matches[] = $row;
            }
        }
        return $matches;
    }

    public function getUnmatchedFoundItems(): array {
        $sql = "
            SELECT
                f.FoundID, f.StudentNumber, f.Location, f.DateFound, f.Category, f.Description, f.Status
            FROM found f
            WHERE f.Status = 'Unclaimed'
            AND f.FoundID NOT IN (
                SELECT f2.FoundID FROM found f2
                INNER JOIN lost l ON
                    f2.Category = l.Category
                    AND DATEDIFF(f2.DateFound, l.DateLost) BETWEEN -3 AND 30
                WHERE l.LostID NOT IN (
                    SELECT OriginalReportID FROM history WHERE ReportType = 'Lost'
                )
            )
            ORDER BY f.DateFound DESC
        ";
        $result = $this->conn->query($sql);
        $items  = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }

    public function verifyMatch(int $lostId, int $foundId): array {
        if ($lostId === 0 || $foundId === 0) {
            return ['status' => 'error', 'message' => 'Invalid request.'];
        }

        $this->conn->begin_transaction();

        try {
            // Fetch lost record
            $lostStmt = $this->conn->prepare(
                'SELECT TicketNumber, StudentNumber, Location, DateLost, Category, Description FROM lost WHERE LostID = ?'
            );
            $lostStmt->bind_param('i', $lostId);
            $lostStmt->execute();
            $lostData = $lostStmt->get_result()->fetch_assoc();

            // Fetch found record
            $foundStmt = $this->conn->prepare(
                'SELECT StudentNumber, DateFound FROM found WHERE FoundID = ?'
            );
            $foundStmt->bind_param('i', $foundId);
            $foundStmt->execute();
            $foundData = $foundStmt->get_result()->fetch_assoc();

            if (!$lostData || !$foundData) {
                throw new Exception('Lost or Found record not found.');
            }

            // Insert lost record into history
            $historyStmt = $this->conn->prepare(
                'INSERT INTO history (ReportType, OriginalReportID, TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $reportType  = 'Lost';
            $finalStatus = 'Retrieved';
            $historyStmt->bind_param('ssissssss',
                $reportType, $lostId, $lostData['TicketNumber'],
                $lostData['StudentNumber'], $lostData['Location'],
                $lostData['DateLost'], $lostData['Category'],
                $lostData['Description'], $finalStatus
            );
            $historyStmt->execute();

            // Insert found record into history
            $foundHistoryStmt = $this->conn->prepare(
                'INSERT INTO history (ReportType, OriginalReportID, TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $foundReportType  = 'Found';
            $foundFinalStatus = 'Claimed';
            $foundTicket      = null;
            $foundHistoryStmt->bind_param('ssissssss',
                $foundReportType, $foundId, $foundTicket,
                $foundData['StudentNumber'], $lostData['Location'],
                $foundData['DateFound'], $lostData['Category'],
                $lostData['Description'], $foundFinalStatus
            );
            $foundHistoryStmt->execute();

            // DELETE from lost table
            $deleteLostStmt = $this->conn->prepare('DELETE FROM lost WHERE LostID = ?');
            $deleteLostStmt->bind_param('i', $lostId);
            $deleteLostStmt->execute();

            // DELETE from found table
            $deleteFoundStmt = $this->conn->prepare('DELETE FROM found WHERE FoundID = ?');
            $deleteFoundStmt->bind_param('i', $foundId);
            $deleteFoundStmt->execute();

            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Match verified successfully.'];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['status' => 'error', 'message' => 'Error verifying match: ' . $e->getMessage()];
        }
    }
}