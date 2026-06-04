<?php

require_once __DIR__ . '/Database.php';

class NotificationService {
    public const PRESETS = [
        'custom' => [
            'label'   => 'Custom message',
            'title'   => '',
            'message' => '',
        ],
        'report_received' => [
            'label'   => 'Report received',
            'title'   => 'Report received',
            'message' => 'Your lost-and-found report has been received by NU Finds. You can track its status anytime from the Track page.',
        ],
        'potential_match' => [
            'label'   => 'Potential match',
            'title'   => 'Potential match found',
            'message' => 'A potential match was found for one of your reports. Please check Track Report or visit the NU Finds help desk for verification.',
        ],
        'item_ready' => [
            'label'   => 'Item ready to claim',
            'title'   => 'Item ready to claim',
            'message' => 'Your item has been verified and is ready for pickup. Visit the NU Finds help desk during office hours with a valid ID.',
        ],
        'action_required' => [
            'label'   => 'Action required',
            'title'   => 'Action required',
            'message' => 'NU Finds needs additional information about your report. Please sign in and check your Track Report page or contact the help desk.',
        ],
    ];

    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public static function presetsForUi(): array {
        $out = [];
        foreach (self::PRESETS as $key => $preset) {
            $out[] = [
                'id'      => $key,
                'label'   => $preset['label'],
                'title'   => $preset['title'],
                'message' => $preset['message'],
            ];
        }

        return $out;
    }

    public function tableExists(): bool {
        $result = $this->conn->query("SHOW TABLES LIKE 'student_notifications'");

        return $result && $result->num_rows > 0;
    }

    public function resolveStudentNumber(string $target): ?string {
        $target = trim($target);
        if ($target === '') {
            return null;
        }

        $stmt = $this->conn->prepare(
            'SELECT StudentNumber FROM studentinfo
             WHERE StudentNumber = ? OR LOWER(StudentEmail) = LOWER(?)
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ss', $target, $target);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row['StudentNumber'] ?? null;
    }

    public function sendToStudent(string $studentNumber, string $title, string $message, ?int $adminId): array {
        if (!$this->tableExists()) {
            return ['status' => 'error', 'message' => 'Notifications table missing. Import database/notifications.sql in phpMyAdmin.'];
        }

        $title   = trim($title);
        $message = trim($message);
        if ($title === '' || $message === '') {
            return ['status' => 'error', 'message' => 'Title and message are required.'];
        }
        if (mb_strlen($title) > 120) {
            return ['status' => 'error', 'message' => 'Title must be 120 characters or less.'];
        }

        if ($this->resolveStudentNumber($studentNumber) === null) {
            return ['status' => 'error', 'message' => 'Student not found. Use student ID or NU email.'];
        }

        $id = $this->insertNotification($studentNumber, $title, $message, $adminId);

        return $id
            ? ['status' => 'success', 'message' => 'Notification sent.', 'sentCount' => 1]
            : ['status' => 'error', 'message' => 'Could not send notification.'];
    }

    public function sendToAll(string $title, string $message, ?int $adminId): array {
        if (!$this->tableExists()) {
            return ['status' => 'error', 'message' => 'Notifications table missing. Import database/notifications.sql in phpMyAdmin.'];
        }

        $title   = trim($title);
        $message = trim($message);
        if ($title === '' || $message === '') {
            return ['status' => 'error', 'message' => 'Title and message are required.'];
        }

        $result = $this->conn->query('SELECT StudentNumber FROM studentinfo');
        if (!$result) {
            return ['status' => 'error', 'message' => 'Could not load students.'];
        }

        $sent = 0;
        while ($row = $result->fetch_assoc()) {
            if ($this->insertNotification($row['StudentNumber'], $title, $message, $adminId)) {
                $sent++;
            }
        }

        if ($sent === 0) {
            return ['status' => 'error', 'message' => 'No students received the notification.'];
        }

        return [
            'status'    => 'success',
            'message'   => "Notification sent to {$sent} student(s).",
            'sentCount' => $sent,
        ];
    }

    public function listForStudent(string $studentNumber, int $limit = 40): array {
        if (!$this->tableExists()) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $stmt  = $this->conn->prepare(
            'SELECT NotificationID, Title, Message, IsRead, CreatedAt
             FROM student_notifications
             WHERE StudentNumber = ?
             ORDER BY CreatedAt DESC
             LIMIT ?'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('si', $studentNumber, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $this->formatRow($row);
            }
        }
        $stmt->close();

        return $rows;
    }

    public function unreadCount(string $studentNumber): int {
        if (!$this->tableExists()) {
            return 0;
        }

        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS cnt FROM student_notifications
             WHERE StudentNumber = ? AND IsRead = 0'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $count  = $result ? (int)($result->fetch_assoc()['cnt'] ?? 0) : 0;
        $stmt->close();

        return $count;
    }

    public function pollForStudent(string $studentNumber, int $sinceId = 0): array {
        $unread = $this->unreadCount($studentNumber);
        $new    = [];

        if ($sinceId > 0 && $this->tableExists()) {
            $stmt = $this->conn->prepare(
                'SELECT NotificationID, Title, Message, IsRead, CreatedAt
                 FROM student_notifications
                 WHERE StudentNumber = ? AND NotificationID > ?
                 ORDER BY NotificationID ASC'
            );
            if ($stmt) {
                $stmt->bind_param('si', $studentNumber, $sinceId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $new[] = $this->formatRow($row);
                    }
                }
                $stmt->close();
            }
        }

        $latestId = 0;
        if ($this->tableExists()) {
            $stmt = $this->conn->prepare(
                'SELECT IFNULL(MAX(NotificationID), 0) AS latest
                 FROM student_notifications WHERE StudentNumber = ?'
            );
            if ($stmt) {
                $stmt->bind_param('s', $studentNumber);
                $stmt->execute();
                $result   = $stmt->get_result();
                $latestId = $result ? (int)($result->fetch_assoc()['latest'] ?? 0) : 0;
                $stmt->close();
            }
        }

        return [
            'unreadCount' => $unread,
            'latestId'    => $latestId,
            'new'         => $new,
        ];
    }

    public function markRead(string $studentNumber, int $notificationId): array {
        if ($notificationId <= 0) {
            return ['status' => 'error', 'message' => 'Invalid notification.'];
        }

        $stmt = $this->conn->prepare(
            'UPDATE student_notifications
             SET IsRead = 1, ReadAt = NOW()
             WHERE NotificationID = ? AND StudentNumber = ?'
        );
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Could not update notification.'];
        }
        $stmt->bind_param('is', $notificationId, $studentNumber);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        return $ok
            ? ['status' => 'success', 'unreadCount' => $this->unreadCount($studentNumber)]
            : ['status' => 'error', 'message' => 'Notification not found.'];
    }

    public function markAllRead(string $studentNumber): array {
        $stmt = $this->conn->prepare(
            'UPDATE student_notifications
             SET IsRead = 1, ReadAt = NOW()
             WHERE StudentNumber = ? AND IsRead = 0'
        );
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Could not update notifications.'];
        }
        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $stmt->close();

        return ['status' => 'success', 'unreadCount' => 0];
    }

    public function listRecentSent(int $limit = 25): array {
        if (!$this->tableExists()) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $sql   = "SELECT n.NotificationID, n.Title, n.Message, n.CreatedAt,
                         n.StudentNumber, s.StudentEmail,
                         a.FullName AS AdminName
                  FROM student_notifications n
                  INNER JOIN studentinfo s ON n.StudentNumber = s.StudentNumber
                  LEFT JOIN adminaccounts a ON n.SentByAdminID = a.AdminID
                  ORDER BY n.CreatedAt DESC
                  LIMIT {$limit}";
        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'id'            => (int)$row['NotificationID'],
                'title'         => $row['Title'],
                'message'       => $row['Message'],
                'studentNumber' => $row['StudentNumber'],
                'studentEmail'  => $row['StudentEmail'],
                'adminName'     => $row['AdminName'] ?? 'Admin',
                'createdAt'     => $row['CreatedAt'],
            ];
        }

        return $rows;
    }

    private function insertNotification(string $studentNumber, string $title, string $message, ?int $adminId): bool {
        if ($adminId !== null && $adminId > 0) {
            $stmt = $this->conn->prepare(
                'INSERT INTO student_notifications (StudentNumber, Title, Message, SentByAdminID)
                 VALUES (?, ?, ?, ?)'
            );
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('sssi', $studentNumber, $title, $message, $adminId);
        } else {
            $stmt = $this->conn->prepare(
                'INSERT INTO student_notifications (StudentNumber, Title, Message)
                 VALUES (?, ?, ?)'
            );
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('sss', $studentNumber, $title, $message);
        }
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    private function formatRow(array $row): array {
        return [
            'id'        => (int)$row['NotificationID'],
            'title'     => $row['Title'],
            'message'   => $row['Message'],
            'isRead'    => (bool)$row['IsRead'],
            'createdAt' => $row['CreatedAt'],
        ];
    }
}
