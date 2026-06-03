<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/DbProcedure.php';

class MatchVerifier {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function getPendingMatches(): array {
        if (DbProcedure::procedureExists($this->conn, 'sp_get_pending_matches')) {
            return DbProcedure::callRows($this->conn, 'sp_get_pending_matches');
        }

        $sql = "
            SELECT
                l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost, l.Category, l.Description,
                f.FoundID, f.StudentNumber AS FoundBy, f.Location AS FoundLocation, f.DateFound, f.Status
            FROM lost l
            INNER JOIN found f ON
                l.Category = f.Category
                AND l.StudentNumber <> f.StudentNumber
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

    public function searchPendingMatches(string $query): array {
        $query = trim($query);
        if ($query === '') {
            return $this->getPendingMatches();
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_admin_search_pending_matches')) {
            return DbProcedure::callRows($this->conn, 'sp_admin_search_pending_matches', 's', [$query]);
        }

        return $this->searchPendingMatchesFallback($query);
    }

    public function verifyMatch(int $lostId, int $foundId): array {
        if ($lostId === 0 || $foundId === 0) {
            return ['status' => 'error', 'message' => 'Invalid request.'];
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_verify_match')) {
            try {
                $ok = DbProcedure::callVoid($this->conn, 'sp_verify_match', 'ii', [$lostId, $foundId]);
                if ($ok) {
                    return ['status' => 'success', 'message' => 'Match verified and archived to history.'];
                }

                return ['status' => 'error', 'message' => $this->conn->error ?: 'Verification failed.'];
            } catch (Throwable $e) {
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $this->verifyMatchLegacy($lostId, $foundId);
    }

    private function verifyMatchLegacy(int $lostId, int $foundId): array {
        $this->conn->begin_transaction();
        try {
            $lostStmt = $this->conn->prepare(
                'SELECT TicketNumber, StudentNumber, Location, DateLost, Category, Description FROM lost WHERE LostID = ?'
            );
            $lostStmt->bind_param('i', $lostId);
            $lostStmt->execute();
            $lostData = $lostStmt->get_result()->fetch_assoc();

            $foundStmt = $this->conn->prepare(
                'SELECT StudentNumber, Location, DateFound, Category, Description FROM found WHERE FoundID = ?'
            );
            $foundStmt->bind_param('i', $foundId);
            $foundStmt->execute();
            $foundData = $foundStmt->get_result()->fetch_assoc();

            if (!$lostData || !$foundData) {
                throw new Exception('Lost or Found record not found.');
            }

            $stmt = $this->conn->prepare(
                'INSERT INTO history (ReportType, OriginalReportID, TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $type = 'Lost';
            $status = 'Retrieved';
            $stmt->bind_param('sisssssss', $type, $lostId, $lostData['TicketNumber'], $lostData['StudentNumber'],
                $lostData['Location'], $lostData['DateLost'], $lostData['Category'], $lostData['Description'], $status);
            $stmt->execute();

            $type = 'Found';
            $status = 'Claimed';
            $ticket = null;
            $stmt->bind_param('sisssssss', $type, $foundId, $ticket, $foundData['StudentNumber'],
                $foundData['Location'], $foundData['DateFound'], $foundData['Category'], $foundData['Description'], $status);
            $stmt->execute();

            $this->conn->query("DELETE FROM lost WHERE LostID = {$lostId}");
            $this->conn->query("DELETE FROM found WHERE FoundID = {$foundId}");

            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Match verified and archived to history.'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function searchPendingMatchesFallback(string $query): array {
        $like = '%' . $query . '%';
        $sql  = "
            SELECT
                l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
                l.Category, l.Description,
                f.FoundID, f.StudentNumber AS FoundBy, f.Location AS FoundLocation,
                f.DateFound, f.Status
            FROM lost l
            INNER JOIN studentinfo s_lost ON l.StudentNumber = s_lost.StudentNumber
            INNER JOIN found f ON
                l.Category = f.Category
                AND l.StudentNumber <> f.StudentNumber
                AND DATEDIFF(f.DateFound, l.DateLost) BETWEEN -3 AND 30
                AND f.Status = 'Unclaimed'
            INNER JOIN studentinfo s_found ON f.StudentNumber = s_found.StudentNumber
            WHERE l.LostID NOT IN (
                SELECT OriginalReportID FROM history WHERE ReportType = 'Lost'
            )
            AND (
                l.TicketNumber LIKE ? OR l.StudentNumber LIKE ? OR s_lost.StudentEmail LIKE ?
                OR l.Location LIKE ? OR l.Category LIKE ? OR l.Description LIKE ?
                OR f.StudentNumber LIKE ? OR s_found.StudentEmail LIKE ? OR f.Location LIKE ?
            )
            ORDER BY l.DateLost DESC
        ";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('sssssssss', $like, $like, $like, $like, $like, $like, $like, $like, $like);
        $stmt->execute();
        $result  = $stmt->get_result();
        $matches = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $matches[] = $row;
            }
        }
        $stmt->close();

        return $matches;
    }
}
