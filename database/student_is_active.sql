-- Run in phpMyAdmin on nufindsdb to enable deactivate/reactivate for students.
ALTER TABLE studentinfo
  ADD COLUMN IsActive tinyint(1) NOT NULL DEFAULT 1 AFTER PasswordHash;

UPDATE studentinfo SET IsActive = 1 WHERE IsActive IS NULL;
