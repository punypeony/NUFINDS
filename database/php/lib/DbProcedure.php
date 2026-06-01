<?php

class DbProcedure {
    public static function callRows(mysqli $conn, string $procedure, string $types = '', array $params = []): array {
        $placeholders = $types !== '' ? implode(',', array_fill(0, strlen($types), '?')) : '';
        $sql          = "CALL {$procedure}({$placeholders})";

        if ($types !== '') {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }

        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            if ($result instanceof mysqli_result) {
                $result->free();
            }
        }

        self::clearResults($conn);

        return $rows;
    }

    public static function callVoid(mysqli $conn, string $procedure, string $types = '', array $params = []): bool {
        $placeholders = $types !== '' ? implode(',', array_fill(0, strlen($types), '?')) : '';
        $sql          = "CALL {$procedure}({$placeholders})";

        if ($types !== '') {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param($types, ...$params);
            $ok = $stmt->execute();
            $stmt->close();
        } else {
            $ok = (bool)$conn->query($sql);
        }

        self::clearResults($conn);

        return $ok;
    }

    public static function procedureExists(mysqli $conn, string $procedure): bool {
        $name = $conn->real_escape_string($procedure);
        $sql  = "SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name = '{$name}'";
        $result = $conn->query($sql);

        return $result && $result->num_rows > 0;
    }

    private static function clearResults(mysqli $conn): void {
        while ($conn->more_results()) {
            $conn->next_result();
            if ($extra = $conn->store_result()) {
                $extra->free();
            }
        }
    }
}
