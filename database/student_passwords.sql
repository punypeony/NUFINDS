-- Run this in phpMyAdmin on database `nufindsdb` if you already imported the database earlier.
-- Adds student password login and sets default password `student123` for all registered students.

ALTER TABLE studentinfo
  ADD COLUMN PasswordHash varchar(255) NOT NULL DEFAULT '' AFTER StudentEmail;

-- Default student password: `student123`
UPDATE studentinfo
SET PasswordHash = '$2y$10$ZfzfxnKoxX1vf8Gl1PDRhuUTYWCkY5LQ8y54cV8YO/3EZwkgcPLl6'
WHERE PasswordHash = '' OR PasswordHash IS NULL;
