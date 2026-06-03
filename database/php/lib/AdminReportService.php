<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/DbProcedure.php';

class AdminReportService {
    private mysqli $conn;

  private const CATEGORIES = [
        'Wallet/Credit Card/Money',
        'Identity Document',
        'Bag',
        'Electronics/Gadgets',
        'Accessories',
        'Others',
    ];

    public function __construct() {
        $this->conn = Database::connect();
    }

    public static function categories(): array {
        return self::CATEGORIES;
    }

    public function getLostGroupedByDepartment(): array {
        if (DbProcedure::procedureExists($this->conn, 'sp_admin_get_lost_reports')) {
            return $this->groupByDepartment(DbProcedure::callRows($this->conn, 'sp_admin_get_lost_reports'));
        }

        $sql = 'SELECT l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
                       l.Category, l.Description, l.Image, l.DateReported,
                       s.CollegeDepartment, s.StudentEmail
                FROM lost l
                INNER JOIN studentinfo s ON l.StudentNumber = s.StudentNumber
                ORDER BY s.CollegeDepartment ASC, l.DateLost DESC';

        return $this->groupByDepartment($this->fetchRows($sql));
    }

    public function getFoundGroupedByDepartment(): array {
        if (DbProcedure::procedureExists($this->conn, 'sp_admin_get_found_reports')) {
            return $this->groupByDepartment(DbProcedure::callRows($this->conn, 'sp_admin_get_found_reports'));
        }

        $sql = 'SELECT f.FoundID, f.StudentNumber, f.Location, f.DateFound,
                       f.Category, f.Description, f.Image, f.Status, f.DateReported,
                       s.CollegeDepartment, s.StudentEmail
                FROM found f
                INNER JOIN studentinfo s ON f.StudentNumber = s.StudentNumber
                ORDER BY s.CollegeDepartment ASC, f.DateFound DESC';

        return $this->groupByDepartment($this->fetchRows($sql));
    }

    public function searchLostGroupedByDepartment(string $query): array {
        $query = trim($query);
        if ($query === '') {
            return $this->getLostGroupedByDepartment();
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_admin_search_lost_reports')) {
            return $this->groupByDepartment(
                DbProcedure::callRows($this->conn, 'sp_admin_search_lost_reports', 's', [$query])
            );
        }

        return $this->groupByDepartment($this->searchLostRowsFallback($query));
    }

    public function searchFoundGroupedByDepartment(string $query): array {
        $query = trim($query);
        if ($query === '') {
            return $this->getFoundGroupedByDepartment();
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_admin_search_found_reports')) {
            return $this->groupByDepartment(
                DbProcedure::callRows($this->conn, 'sp_admin_search_found_reports', 's', [$query])
            );
        }

        return $this->groupByDepartment($this->searchFoundRowsFallback($query));
    }

    public function updateLost(int $lostId, array $data): array {
        if ($lostId <= 0) {
            return ['status' => 'error', 'message' => 'Invalid lost report.'];
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_admin_update_lost')) {
            $ok = DbProcedure::callVoid(
                $this->conn,
                'sp_admin_update_lost',
                'issssss',
                [
                    $lostId,
                    $data['TicketNumber'],
                    $data['StudentNumber'],
                    $data['Location'],
                    $data['DateLost'],
                    $data['Category'],
                    $data['Description'],
                ]
            );
            return $ok
                ? ['status' => 'success', 'message' => 'Lost report updated.']
                : ['status' => 'error', 'message' => $this->conn->error];
        }

        $stmt = $this->conn->prepare(
            'UPDATE lost SET TicketNumber=?, StudentNumber=?, Location=?, DateLost=?, Category=?, Description=? WHERE LostID=?'
        );
        $stmt->bind_param(
            'ssssssi',
            $data['TicketNumber'],
            $data['StudentNumber'],
            $data['Location'],
            $data['DateLost'],
            $data['Category'],
            $data['Description'],
            $lostId
        );

        return $stmt->execute()
            ? ['status' => 'success', 'message' => 'Lost report updated.']
            : ['status' => 'error', 'message' => $stmt->error];
    }

    public function deleteLost(int $lostId): array {
        if (DbProcedure::procedureExists($this->conn, 'sp_admin_delete_lost')) {
            DbProcedure::callVoid($this->conn, 'sp_admin_delete_lost', 'i', [$lostId]);
            return ['status' => 'success', 'message' => 'Lost report deleted.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM lost WHERE LostID = ?');
        $stmt->bind_param('i', $lostId);

        return $stmt->execute() && $stmt->affected_rows > 0
            ? ['status' => 'success', 'message' => 'Lost report deleted.']
            : ['status' => 'error', 'message' => 'Could not delete lost report.'];
    }

    public function updateFound(int $foundId, array $data): array {
        if ($foundId <= 0) {
            return ['status' => 'error', 'message' => 'Invalid found report.'];
        }

        if (DbProcedure::procedureExists($this->conn, 'sp_admin_update_found')) {
            $ok = DbProcedure::callVoid(
                $this->conn,
                'sp_admin_update_found',
                'issssss',
                [
                    $foundId,
                    $data['StudentNumber'],
                    $data['Location'],
                    $data['DateFound'],
                    $data['Category'],
                    $data['Description'],
                    $data['Status'],
                ]
            );
            return $ok
                ? ['status' => 'success', 'message' => 'Found report updated.']
                : ['status' => 'error', 'message' => $this->conn->error];
        }

        $stmt = $this->conn->prepare(
            'UPDATE found SET StudentNumber=?, Location=?, DateFound=?, Category=?, Description=?, Status=? WHERE FoundID=?'
        );
        $stmt->bind_param(
            'ssssssi',
            $data['StudentNumber'],
            $data['Location'],
            $data['DateFound'],
            $data['Category'],
            $data['Description'],
            $data['Status'],
            $foundId
        );

        return $stmt->execute()
            ? ['status' => 'success', 'message' => 'Found report updated.']
            : ['status' => 'error', 'message' => $stmt->error];
    }

    public function deleteFound(int $foundId): array {
        if (DbProcedure::procedureExists($this->conn, 'sp_admin_delete_found')) {
            DbProcedure::callVoid($this->conn, 'sp_admin_delete_found', 'i', [$foundId]);
            return ['status' => 'success', 'message' => 'Found report deleted.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM found WHERE FoundID = ?');
        $stmt->bind_param('i', $foundId);

        return $stmt->execute() && $stmt->affected_rows > 0
            ? ['status' => 'success', 'message' => 'Found report deleted.']
            : ['status' => 'error', 'message' => 'Could not delete found report.'];
    }

    private function fetchRows(string $sql): array {
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

    private function groupByDepartment(array $rows): array {
        $grouped = [];
        foreach ($rows as $row) {
            $dept = $row['CollegeDepartment'] ?: 'Unknown Department';
            $grouped[$dept][] = $row;
        }

        return $grouped;
    }

    private function searchLostRowsFallback(string $query): array {
        $like = '%' . $query . '%';
        $sql  = 'SELECT l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
                        l.Category, l.Description, l.Image, l.DateReported,
                        s.CollegeDepartment, s.StudentEmail
                 FROM lost l
                 INNER JOIN studentinfo s ON l.StudentNumber = s.StudentNumber
                 WHERE l.TicketNumber LIKE ? OR l.StudentNumber LIKE ? OR s.StudentEmail LIKE ?
                    OR l.Location LIKE ? OR l.Category LIKE ? OR l.Description LIKE ?
                 ORDER BY s.CollegeDepartment ASC, l.DateLost DESC';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ssssss', $like, $like, $like, $like, $like, $like);
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

    private function searchFoundRowsFallback(string $query): array {
        $like = '%' . $query . '%';
        $sql  = 'SELECT f.FoundID, f.StudentNumber, f.Location, f.DateFound,
                        f.Category, f.Description, f.Image, f.Status, f.DateReported,
                        s.CollegeDepartment, s.StudentEmail
                 FROM found f
                 INNER JOIN studentinfo s ON f.StudentNumber = s.StudentNumber
                 WHERE CAST(f.FoundID AS CHAR) LIKE ? OR f.StudentNumber LIKE ? OR s.StudentEmail LIKE ?
                    OR f.Location LIKE ? OR f.Category LIKE ? OR f.Description LIKE ? OR f.Status LIKE ?
                 ORDER BY s.CollegeDepartment ASC, f.DateFound DESC';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('sssssss', $like, $like, $like, $like, $like, $like, $like);
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
}
