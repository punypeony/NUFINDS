<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/DbProcedure.php';

class HistoryService {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function getArchivedMatches(): array {
        $rows = DbProcedure::procedureExists($this->conn, 'sp_admin_get_history')
            ? DbProcedure::callRows($this->conn, 'sp_admin_get_history')
            : $this->fetchAll('SELECT * FROM history ORDER BY DateCompleted DESC, HistoryID DESC');

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
