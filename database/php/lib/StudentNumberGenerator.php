<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/DbProcedure.php';

class StudentNumberGenerator {
    public static function generate(mysqli $conn = null): string {
        $conn = $conn ?? Database::connect();

        if (DbProcedure::procedureExists($conn, 'sp_generate_student_number')) {
            $rows = DbProcedure::callRows($conn, 'sp_generate_student_number');
            if ($rows !== [] && !empty($rows[0]['StudentNumber'])) {
                return (string)$rows[0]['StudentNumber'];
            }
        }

        return self::generateFallback($conn);
    }

    private static function generateFallback(mysqli $conn): string {
        $year = (int)date('Y');
        $pattern = $year . '-%';

        $stmt = $conn->prepare(
            'SELECT StudentNumber FROM studentinfo
             WHERE StudentNumber LIKE ?
             ORDER BY StudentNumber DESC
             LIMIT 1'
        );
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        $next = 1;
        if ($row && preg_match('/^' . $year . '-(\d{4})$/', $row['StudentNumber'], $m)) {
            $next = (int)$m[1] + 1;
        }

        return $year . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }
}
