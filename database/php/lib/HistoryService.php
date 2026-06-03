<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/DbProcedure.php';

class HistoryService {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function getArchivedMatches(): array {
        return $this->groupHistoryRows($this->fetchHistoryRows(''));
    }

    public function searchArchivedMatches(string $query): array {
        return $this->groupHistoryRows($this->fetchHistoryRows(trim($query)));
    }

    public function getTotalCount(): int {
        $result = $this->conn->query('SELECT COUNT(*) AS cnt FROM history');

        return $result ? (int)($result->fetch_assoc()['cnt'] ?? 0) : 0;
    }

    public function updateHistory(int $historyId, array $data): array {
        if ($historyId <= 0) {
            return ['status' => 'error', 'message' => 'Invalid history record.'];
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_admin_update_history')) {
            $ok = DbProcedure::callVoid(
                $this->conn,
                'sp_admin_update_history',
                'isssssss',
                [
                    $historyId,
                    $data['TicketNumber'],
                    $data['StudentNumber'],
                    $data['Location'],
                    $data['ReportDate'],
                    $data['Category'],
                    $data['Description'],
                    $data['FinalStatus'],
                ]
            );
            return $ok
                ? ['status' => 'success', 'message' => 'History record updated.']
                : ['status' => 'error', 'message' => $this->conn->error];
        }

        $stmt = $this->conn->prepare(
            'UPDATE history SET TicketNumber=?, StudentNumber=?, Location=?, ReportDate=?, Category=?, Description=?, FinalStatus=? WHERE HistoryID=?'
        );
        $stmt->bind_param(
            'sssssssi',
            $data['TicketNumber'],
            $data['StudentNumber'],
            $data['Location'],
            $data['ReportDate'],
            $data['Category'],
            $data['Description'],
            $data['FinalStatus'],
            $historyId
        );

        return $stmt->execute()
            ? ['status' => 'success', 'message' => 'History record updated.']
            : ['status' => 'error', 'message' => $stmt->error];
    }

    public function deleteHistory(int $historyId): array {
        if (DbProcedure::procedureExists($this->conn, 'sp_admin_delete_history')) {
            DbProcedure::callVoid($this->conn, 'sp_admin_delete_history', 'i', [$historyId]);
            return ['status' => 'success', 'message' => 'History record deleted.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM history WHERE HistoryID = ?');
        $stmt->bind_param('i', $historyId);

        return $stmt->execute() && $stmt->affected_rows > 0
            ? ['status' => 'success', 'message' => 'History record deleted.']
            : ['status' => 'error', 'message' => 'Could not delete history record.'];
    }

    private function fetchAll(string $sql): array {
        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function hasMatchGroupColumn(): bool {
        $result = $this->conn->query("SHOW COLUMNS FROM history LIKE 'MatchGroupID'");

        return $result && $result->num_rows > 0;
    }

    private function fetchHistoryRows(string $query): array {
        if ($query !== '' && DbProcedure::procedureExists($this->conn, 'sp_admin_search_history')) {
            return DbProcedure::callRows($this->conn, 'sp_admin_search_history', 's', [$query]);
        }

        if ($query !== '') {
            return $this->searchHistoryRowsFallback($query);
        }

        return DbProcedure::procedureExists($this->conn, 'sp_admin_get_history')
            ? DbProcedure::callRows($this->conn, 'sp_admin_get_history')
            : $this->fetchAll('SELECT * FROM history ORDER BY DateCompleted DESC, HistoryID DESC');
    }

    private function groupHistoryRows(array $rows): array {
        if (!$this->hasMatchGroupColumn()) {
            return $this->wrapLegacyRows($rows);
        }

        $groups = [];
        foreach ($rows as $row) {
            $key = $row['MatchGroupID'] ?: ('legacy-' . $row['HistoryID']);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'matchGroupId'  => $row['MatchGroupID'] ?? null,
                    'dateCompleted' => $row['DateCompleted'],
                    'lost'          => null,
                    'found'         => null,
                ];
            }
            if ($row['ReportType'] === 'Lost') {
                $groups[$key]['lost'] = $row;
            } elseif ($row['ReportType'] === 'Found') {
                $groups[$key]['found'] = $row;
            }
        }

        return array_values($groups);
    }

    private function searchHistoryRowsFallback(string $query): array {
        $like = '%' . $query . '%';
        $sql  = 'SELECT h.*
                 FROM history h
                 LEFT JOIN studentinfo s ON h.StudentNumber = s.StudentNumber
                 WHERE IFNULL(h.TicketNumber, \'\') LIKE ? OR h.StudentNumber LIKE ?
                    OR IFNULL(s.StudentEmail, \'\') LIKE ? OR h.Location LIKE ?
                    OR h.Category LIKE ? OR h.Description LIKE ? OR h.FinalStatus LIKE ?
                    OR IFNULL(h.MatchGroupID, \'\') LIKE ? OR CAST(h.HistoryID AS CHAR) LIKE ?
                 ORDER BY h.DateCompleted DESC, h.HistoryID DESC';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ssssssss', $like, $like, $like, $like, $like, $like, $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();

        return $rows;
    }

    private function wrapLegacyRows(array $rows): array {
        $groups = [];
        foreach ($rows as $row) {
            $groups[] = [
                'matchGroupId'  => null,
                'dateCompleted' => $row['DateCompleted'],
                'lost'          => $row['ReportType'] === 'Lost' ? $row : null,
                'found'         => $row['ReportType'] === 'Found' ? $row : null,
            ];
        }

        return $groups;
    }
}
