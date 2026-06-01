-- Run this in phpMyAdmin on database `nufindsdb` (new installs or upgrade).

CREATE TABLE IF NOT EXISTS adminaccounts (
  AdminID int(11) NOT NULL AUTO_INCREMENT,
  Username varchar(50) NOT NULL,
  AdminEmail varchar(100) NOT NULL,
  PasswordHash varchar(255) NOT NULL,
  FullName varchar(100) NOT NULL,
  IsActive tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (AdminID),
  UNIQUE KEY Username (Username),
  UNIQUE KEY AdminEmail (AdminEmail)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- If table exists without AdminEmail column:
-- ALTER TABLE adminaccounts ADD COLUMN AdminEmail varchar(100) NOT NULL AFTER Username;
-- UPDATE adminaccounts SET AdminEmail = 'nufindshelpdesk@gmail.com' WHERE Username = 'admin';

-- Default admin: email `nufindshelpdesk@gmail.com`, password `admin123`
INSERT INTO adminaccounts (Username, AdminEmail, PasswordHash, FullName, IsActive) VALUES
('admin', 'nufindshelpdesk@gmail.com', '$2y$10$2TIFEo46Xc4NL2hUU2pq1ehTvp/FIP47AThTRxdeSfG8Vj.a04Qe6', 'NU Finds Administrator', 1)
ON DUPLICATE KEY UPDATE
  AdminEmail = VALUES(AdminEmail),
  PasswordHash = VALUES(PasswordHash),
  FullName = VALUES(FullName),
  IsActive = VALUES(IsActive);
