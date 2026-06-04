-- Run in phpMyAdmin on database `nufindsdb` after nufindsdb.sql

CREATE TABLE IF NOT EXISTS student_notifications (
  NotificationID int(11) NOT NULL AUTO_INCREMENT,
  StudentNumber varchar(20) NOT NULL,
  Title varchar(120) NOT NULL,
  Message text NOT NULL,
  SentByAdminID int(11) DEFAULT NULL,
  IsRead tinyint(1) NOT NULL DEFAULT 0,
  ReadAt datetime DEFAULT NULL,
  CreatedAt timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (NotificationID),
  KEY idx_student_unread (StudentNumber, IsRead, CreatedAt),
  KEY idx_created (CreatedAt),
  CONSTRAINT fk_notification_student
    FOREIGN KEY (StudentNumber) REFERENCES studentinfo (StudentNumber) ON DELETE CASCADE,
  CONSTRAINT fk_notification_admin
    FOREIGN KEY (SentByAdminID) REFERENCES adminaccounts (AdminID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
