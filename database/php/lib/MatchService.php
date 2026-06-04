<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/DbProcedure.php';

class MatchService {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public static function tableExists(mysqli $conn): bool {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $result = $conn->query("SHOW TABLES LIKE 'matches'");
        $cached = $result && $result->num_rows > 0;

        return $cached;
    }

    private static function historyHasMatchId(mysqli $conn): bool {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $result = $conn->query("SHOW COLUMNS FROM history LIKE 'MatchID'");
        $cached = $result && $result->num_rows > 0;

        return $cached;
    }

    public function syncPending(): void {
        if (!self::tableExists($this->conn)) {
            return;
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_sync_pending_matches')) {
            DbProcedure::callVoid($this->conn, 'sp_sync_pending_matches');
            return;
        }

        $this->removeStalePending();
        $this->insertNewPending();
    }

    private function removeStalePending(): void {
        $sql = "
            DELETE m FROM matches m
            LEFT JOIN lost l ON m.LostID = l.LostID
            LEFT JOIN found f ON m.FoundID = f.FoundID
            WHERE m.Status = 'pending'
              AND (
                l.LostID IS NULL
                OR f.FoundID IS NULL
                OR f.Status <> 'Unclaimed'
                OR l.Category <> f.Category
                OR l.StudentNumber = f.StudentNumber
                OR DATEDIFF(f.DateFound, l.DateLost) NOT BETWEEN -3 AND 30
              )
        ";
        $this->conn->query($sql);
    }

    private function insertNewPending(): void {
        $sql = "
            INSERT IGNORE INTO matches (LostID, FoundID, Status)
            SELECT l.LostID, f.FoundID, 'pending'
            FROM lost l
            INNER JOIN found f ON
                l.Category = f.Category
                AND l.StudentNumber <> f.StudentNumber
                AND DATEDIFF(f.DateFound, l.DateLost) BETWEEN -3 AND 30
                AND f.Status = 'Unclaimed'
        ";
        $this->conn->query($sql);
    }

    public function getPendingMatches(): array {
        $this->syncPending();

        if (DbProcedure::procedureExists($this->conn, 'sp_get_pending_matches')) {
            return DbProcedure::callRows($this->conn, 'sp_get_pending_matches');
        }

        return $this->fetchPendingRows('');
    }

    public function searchPendingMatches(string $query): array {
        $query = trim($query);
        if ($query === '') {
            return $this->getPendingMatches();
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_admin_search_pending_matches')) {
            return DbProcedure::callRows($this->conn, 'sp_admin_search_pending_matches', 's', [$query]);
        }

        return $this->fetchPendingRows($query);
    }

    private function fetchPendingRows(string $query): array {
        $sql = "
            SELECT
                m.MatchID,
                l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
                l.Category, l.Description,
                f.FoundID, f.StudentNumber AS FoundBy, f.Location AS FoundLocation,
                f.DateFound, f.Status
            FROM matches m
            INNER JOIN lost l ON m.LostID = l.LostID
            INNER JOIN found f ON m.FoundID = f.FoundID
            WHERE m.Status = 'pending'
        ";

        $params = [];
        $types  = '';

        if ($query !== '') {
            $like = '%' . $query . '%';
            $sql .= "
              AND (
                l.TicketNumber LIKE ?
                OR l.StudentNumber LIKE ?
                OR l.Location LIKE ?
                OR l.Category LIKE ?
                OR l.Description LIKE ?
                OR f.StudentNumber LIKE ?
                OR f.Location LIKE ?
                OR CAST(m.MatchID AS CHAR) LIKE ?
              )
            ";
            $types  = 'ssssssss';
            $params = array_fill(0, 8, $like);
        }

        $sql .= ' ORDER BY m.CreatedAt DESC, l.DateLost DESC';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

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

    public function verifyMatch(int $matchId, int $lostId, int $foundId, ?int $adminId): array {
        if (!self::tableExists($this->conn)) {
            return ['status' => 'error', 'message' => 'Matches table missing. Import database/matches.sql in phpMyAdmin.'];
        }

        $this->syncPending();

        if ($matchId <= 0 && $lostId > 0 && $foundId > 0) {
            $matchId = $this->resolvePendingMatchId($lostId, $foundId);
        }

        if ($matchId <= 0) {
            return ['status' => 'error', 'message' => 'Invalid or expired match. Refresh the page.'];
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_verify_match')) {
            try {
                $ok = DbProcedure::callVoid(
                    $this->conn,
                    'sp_verify_match',
                    'iii',
                    [$matchId, $lostId, $foundId]
                );
                if ($ok) {
                    return ['status' => 'success', 'message' => 'Match verified and archived to history.'];
                }

                return ['status' => 'error', 'message' => $this->conn->error ?: 'Verification failed.'];
            } catch (Throwable $e) {
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $this->verifyMatchPhp($matchId, $lostId, $foundId, $adminId);
    }

    public function rejectMatch(int $matchId, ?int $adminId): array {
        if (!self::tableExists($this->conn)) {
            return ['status' => 'error', 'message' => 'Matches table missing. Import database/matches.sql in phpMyAdmin.'];
        }

        if ($matchId <= 0) {
            return ['status' => 'error', 'message' => 'Invalid match.'];
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_reject_match')) {
            try {
                $ok = DbProcedure::callVoid($this->conn, 'sp_reject_match', 'ii', [$matchId, $adminId ?? 0]);
                if ($ok) {
                    return ['status' => 'success', 'message' => 'Match dismissed. Reports stay active.'];
                }

                return ['status' => 'error', 'message' => $this->conn->error ?: 'Could not dismiss match.'];
            } catch (Throwable $e) {
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        $stmt = $this->conn->prepare(
            "UPDATE matches
             SET Status = 'rejected', RejectedAt = CURRENT_TIMESTAMP, RejectedByAdminID = ?
             WHERE MatchID = ? AND Status = 'pending'"
        );
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Could not dismiss match.'];
        }
        $stmt->bind_param('ii', $adminId, $matchId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0
            ? ['status' => 'success', 'message' => 'Match dismissed. Reports stay active.']
            : ['status' => 'error', 'message' => 'Match not found or already handled.'];
    }

    private function resolvePendingMatchId(int $lostId, int $foundId): int {
        $stmt = $this->conn->prepare(
            "SELECT MatchID FROM matches WHERE LostID = ? AND FoundID = ? AND Status = 'pending' LIMIT 1"
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ii', $lostId, $foundId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['MatchID'] ?? 0);
    }

    private function verifyMatchPhp(int $matchId, int $lostId, int $foundId, ?int $adminId): array {
        $stmt = $this->conn->prepare(
            "SELECT MatchID, LostID, FoundID FROM matches WHERE MatchID = ? AND Status = 'pending' LIMIT 1"
        );
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Could not load match.'];
        }
        $stmt->bind_param('i', $matchId);
        $stmt->execute();
        $match = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$match) {
            return ['status' => 'error', 'message' => 'Match not found or already handled.'];
        }

        $lostId  = (int)$match['LostID'];
        $foundId = (int)$match['FoundID'];

        $this->conn->begin_transaction();
        try {
            $lostStmt = $this->conn->prepare(
                'SELECT TicketNumber, StudentNumber, Location, DateLost, Category, Description FROM lost WHERE LostID = ?'
            );
            $lostStmt->bind_param('i', $lostId);
            $lostStmt->execute();
            $lostData = $lostStmt->get_result()->fetch_assoc();
            $lostStmt->close();

            $foundStmt = $this->conn->prepare(
                'SELECT StudentNumber, Location, DateFound, Category, Description FROM found WHERE FoundID = ?'
            );
            $foundStmt->bind_param('i', $foundId);
            $foundStmt->execute();
            $foundData = $foundStmt->get_result()->fetch_assoc();
            $foundStmt->close();

            if (!$lostData || !$foundData) {
                throw new Exception('Lost or Found record not found.');
            }

            $matchGroup = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );

            $hasGroup   = $this->historyColumnExists('MatchGroupID');
            $hasMatchId = self::historyHasMatchId($this->conn);

            if ($hasGroup) {
                $this->archivePairToHistory(
                    $matchGroup,
                    $hasMatchId ? $matchId : null,
                    $lostId,
                    $foundId,
                    $lostData,
                    $foundData
                );
            } else {
                $this->insertHistoryRowMinimal('Lost', $lostId, $lostData, 'Retrieved');
                $this->insertHistoryRowMinimal('Found', $foundId, $foundData, 'Claimed');
            }

            $upd = $this->conn->prepare(
                "UPDATE matches
                 SET Status = 'verified', VerifiedAt = CURRENT_TIMESTAMP, VerifiedByAdminID = ?
                 WHERE MatchID = ?"
            );
            $upd->bind_param('ii', $adminId, $matchId);
            $upd->execute();
            $upd->close();

            $this->conn->query("DELETE FROM lost WHERE LostID = {$lostId}");
            $this->conn->query("DELETE FROM found WHERE FoundID = {$foundId}");

            $this->conn->commit();

            return ['status' => 'success', 'message' => 'Match verified and archived to history.'];
        } catch (Exception $e) {
            $this->conn->rollback();

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function historyColumnExists(string $column): bool {
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'history\' AND COLUMN_NAME = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['cnt'] ?? 0) > 0;
    }

    private function archivePairToHistory(
        string $matchGroup,
        ?int $matchId,
        int $lostId,
        int $foundId,
        array $lostData,
        array $foundData
    ): void {
        $hasMatchId = $matchId !== null && self::historyHasMatchId($this->conn);
        $hasLostId  = $this->historyColumnExists('LostID');

        if ($hasMatchId && $hasLostId) {
            $this->insertHistoryRowFull($matchGroup, $matchId, $lostId, $foundId, 'Lost', $lostId,
                $lostData['TicketNumber'], $lostData['StudentNumber'], $lostData['Location'],
                $lostData['DateLost'], $lostData['Category'], $lostData['Description'], 'Retrieved');
            $this->insertHistoryRowFull($matchGroup, $matchId, $lostId, $foundId, 'Found', $foundId,
                null, $foundData['StudentNumber'], $foundData['Location'],
                $foundData['DateFound'], $foundData['Category'], $foundData['Description'], 'Claimed');
            return;
        }

        if ($hasLostId) {
            $typeLost   = 'Lost';
            $statusLost = 'Retrieved';
            $stmt       = $this->conn->prepare(
                'INSERT INTO history (MatchGroupID, LostID, FoundID, ReportType, OriginalReportID,
                    TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'siisisssssss',
                $matchGroup,
                $lostId,
                $foundId,
                $typeLost,
                $lostId,
                $lostData['TicketNumber'],
                $lostData['StudentNumber'],
                $lostData['Location'],
                $lostData['DateLost'],
                $lostData['Category'],
                $lostData['Description'],
                $statusLost
            );
            $stmt->execute();
            $stmt->close();

            $typeFound   = 'Found';
            $statusFound = 'Claimed';
            $ticket      = null;
            $stmt        = $this->conn->prepare(
                'INSERT INTO history (MatchGroupID, LostID, FoundID, ReportType, OriginalReportID,
                    TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'siisisssssss',
                $matchGroup,
                $lostId,
                $foundId,
                $typeFound,
                $foundId,
                $ticket,
                $foundData['StudentNumber'],
                $foundData['Location'],
                $foundData['DateFound'],
                $foundData['Category'],
                $foundData['Description'],
                $statusFound
            );
            $stmt->execute();
            $stmt->close();

            return;
        }

        $this->insertHistoryRowMinimal('Lost', $lostId, $lostData, 'Retrieved');
        $this->insertHistoryRowMinimal('Found', $foundId, $foundData, 'Claimed');
    }

    private function insertHistoryRowFull(
        string $matchGroup,
        int $matchId,
        int $lostId,
        int $foundId,
        string $type,
        int $originalId,
        ?string $ticket,
        string $student,
        string $location,
        string $reportDate,
        string $category,
        string $description,
        string $finalStatus
    ): void {
        $stmt = $this->conn->prepare(
            'INSERT INTO history (MatchGroupID, MatchID, LostID, FoundID, ReportType, OriginalReportID,
                TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'siiiissssssss',
            $matchGroup,
            $matchId,
            $lostId,
            $foundId,
            $type,
            $originalId,
            $ticket,
            $student,
            $location,
            $reportDate,
            $category,
            $description,
            $finalStatus
        );
        $stmt->execute();
        $stmt->close();
    }

    private function insertHistoryRowMinimal(string $type, int $originalId, array $data, string $finalStatus): void {
        $stmt = $this->conn->prepare(
            'INSERT INTO history (ReportType, OriginalReportID, TicketNumber, StudentNumber, Location,
                ReportDate, Category, Description, FinalStatus)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if ($type === 'Lost') {
            $stmt->bind_param(
                'sisssssss',
                $type,
                $originalId,
                $data['TicketNumber'],
                $data['StudentNumber'],
                $data['Location'],
                $data['DateLost'],
                $data['Category'],
                $data['Description'],
                $finalStatus
            );
        } else {
            $ticket = null;
            $stmt->bind_param(
                'sisssssss',
                $type,
                $originalId,
                $ticket,
                $data['StudentNumber'],
                $data['Location'],
                $data['DateFound'],
                $data['Category'],
                $data['Description'],
                $finalStatus
            );
        }
        $stmt->execute();
        $stmt->close();
    }

    public function cleanupForLost(int $lostId): void {
        if (!self::tableExists($this->conn) || $lostId <= 0) {
            return;
        }
        $stmt = $this->conn->prepare("DELETE FROM matches WHERE LostID = ? AND Status = 'pending'");
        $stmt->bind_param('i', $lostId);
        $stmt->execute();
        $stmt->close();
    }

    public function cleanupForFound(int $foundId): void {
        if (!self::tableExists($this->conn) || $foundId <= 0) {
            return;
        }
        $stmt = $this->conn->prepare("DELETE FROM matches WHERE FoundID = ? AND Status = 'pending'");
        $stmt->bind_param('i', $foundId);
        $stmt->execute();
        $stmt->close();
    }
}
