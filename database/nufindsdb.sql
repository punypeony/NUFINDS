-- phpMyAdmin SQL Dump
-- NUFinds Database - Fixed Version

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Database: nufindsdb

-- --------------------------------------------------------
-- Table structure for table found
-- --------------------------------------------------------

CREATE TABLE found (
  FoundID int(11) NOT NULL,
  StudentNumber varchar(20) NOT NULL,
  Location varchar(255) NOT NULL,
  DateFound date NOT NULL,
  Category enum('Wallet/Credit Card/Money','Identity Document','Bag','Electronics/Gadgets','Accessories','Others') NOT NULL,
  Description text NOT NULL,
  Status varchar(20) DEFAULT 'Unclaimed',
  DateReported timestamp NOT NULL DEFAULT current_timestamp(),
  Image VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table history
-- --------------------------------------------------------

CREATE TABLE history (
  HistoryID int(11) NOT NULL,
  MatchGroupID varchar(36) DEFAULT NULL,
  LostID int(11) DEFAULT NULL,
  FoundID int(11) DEFAULT NULL,
  ReportType enum('Lost','Found') NOT NULL,
  OriginalReportID int(11) NOT NULL,
  TicketNumber varchar(10) DEFAULT NULL,
  StudentNumber varchar(20) NOT NULL,
  Location varchar(255) NOT NULL,
  ReportDate date NOT NULL,
  Category varchar(100) NOT NULL,
  Description text NOT NULL,
  FinalStatus varchar(50) NOT NULL,
  DateCompleted timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table lost
-- --------------------------------------------------------

CREATE TABLE lost (
  LostID int(11) NOT NULL,
  TicketNumber varchar(10) NOT NULL,
  StudentNumber varchar(20) NOT NULL,
  Location varchar(255) NOT NULL,
  DateLost date NOT NULL,
  Category enum('Wallet/Credit Card/Money','Identity Document','Bag','Electronics/Gadgets','Accessories','Others') NOT NULL,
  Description text NOT NULL,
  DateReported timestamp NOT NULL DEFAULT current_timestamp(),
  Image VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table studentinfo
-- --------------------------------------------------------

CREATE TABLE studentinfo (
  StudentNumber varchar(20) NOT NULL,
  CollegeDepartment enum('COLLEGE OF ALLIED HEALTH','COLLEGE OF ARCHITECTURE','COLLEGE OF BUSINESS AND ACCOUNTANCY','COLLEGE OF COMPUTING AND INFORMATION TECHNOLOGIES','COLLEGE OF EDUCATION ARTS AND SCIENCES','COLLEGE OF ENGINEERING','COLLEGE OF TOURISM AND HOSPITALITY MANAGEMENT') NOT NULL,
  StudentEmail varchar(50) NOT NULL,
  PasswordHash varchar(255) NOT NULL,
  IsActive tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table adminaccounts
-- --------------------------------------------------------

CREATE TABLE adminaccounts (
  AdminID int(11) NOT NULL,
  Username varchar(50) NOT NULL,
  AdminEmail varchar(100) NOT NULL,
  PasswordHash varchar(255) NOT NULL,
  FullName varchar(100) NOT NULL,
  IsActive tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default admin: email `nufindshelpdesk@gmail.com`, password `admin123`
INSERT INTO adminaccounts (AdminID, Username, AdminEmail, PasswordHash, FullName, IsActive) VALUES
(1, 'admin', 'nufindshelpdesk@gmail.com', '$2y$10$2TIFEo46Xc4NL2hUU2pq1ehTvp/FIP47AThTRxdeSfG8Vj.a04Qe6', 'NU Finds Administrator', 1);

-- --------------------------------------------------------
-- Indexes
-- --------------------------------------------------------

ALTER TABLE adminaccounts
  ADD PRIMARY KEY (AdminID),
  ADD UNIQUE KEY Username (Username),
  ADD UNIQUE KEY AdminEmail (AdminEmail);

ALTER TABLE found
  ADD PRIMARY KEY (FoundID),
  ADD KEY StudentNumber (StudentNumber);

ALTER TABLE history
  ADD PRIMARY KEY (HistoryID),
  ADD KEY idx_match_group (MatchGroupID);

ALTER TABLE lost
  ADD PRIMARY KEY (LostID),
  ADD UNIQUE KEY TicketNumber (TicketNumber),
  ADD KEY StudentNumber (StudentNumber);

ALTER TABLE studentinfo
  ADD PRIMARY KEY (StudentNumber),
  ADD UNIQUE KEY StudentEmail_UNIQUE (StudentEmail);

-- --------------------------------------------------------
-- AUTO_INCREMENT
-- --------------------------------------------------------

ALTER TABLE found
  MODIFY FoundID int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE history
  MODIFY HistoryID int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE lost
  MODIFY LostID int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE adminaccounts
  MODIFY AdminID int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Foreign Keys
-- --------------------------------------------------------

ALTER TABLE found
  ADD CONSTRAINT found_ibfk_1 FOREIGN KEY (StudentNumber) REFERENCES studentinfo (StudentNumber);

ALTER TABLE lost
  ADD CONSTRAINT lost_ibfk_1 FOREIGN KEY (StudentNumber) REFERENCES studentinfo (StudentNumber);

-- --------------------------------------------------------
-- Sample Data - studentinfo
-- --------------------------------------------------------

-- Default student password for sample accounts: `student123`
INSERT INTO studentinfo (StudentNumber, CollegeDepartment, StudentEmail, PasswordHash) VALUES
('2024-1001234', 'COLLEGE OF COMPUTING AND INFORMATION TECHNOLOGIES', 'juansantos@students.national-u.edu.ph', '$2y$10$ZfzfxnKoxX1vf8Gl1PDRhuUTYWCkY5LQ8y54cV8YO/3EZwkgcPLl6'),
('2024-1005678', 'COLLEGE OF BUSINESS AND ACCOUNTANCY', 'mariacruz@students.national-u.edu.ph', '$2y$10$ZfzfxnKoxX1vf8Gl1PDRhuUTYWCkY5LQ8y54cV8YO/3EZwkgcPLl6'),
('2024-1009012', 'COLLEGE OF ENGINEERING', 'saramarcos@students.national-u.edu.ph', '$2y$10$ZfzfxnKoxX1vf8Gl1PDRhuUTYWCkY5LQ8y54cV8YO/3EZwkgcPLl6'),
('2024-1003456', 'COLLEGE OF ARCHITECTURE', 'rodrigodelarosa@students.national-u.edu.ph', '$2y$10$ZfzfxnKoxX1vf8Gl1PDRhuUTYWCkY5LQ8y54cV8YO/3EZwkgcPLl6'),
('2024-1007890', 'COLLEGE OF EDUCATION ARTS AND SCIENCES', 'briantan@students.national-u.edu.ph', '$2y$10$ZfzfxnKoxX1vf8Gl1PDRhuUTYWCkY5LQ8y54cV8YO/3EZwkgcPLl6');

-- --------------------------------------------------------
-- Sample Data - lost
-- --------------------------------------------------------

INSERT INTO lost (LostID, TicketNumber, StudentNumber, Location, DateLost, Category, Description, DateReported) VALUES
(1, 'NU-1001', '2024-1001234', 'Library Second Floor', '2026-05-15', 'Wallet/Credit Card/Money', 'Black leather wallet with student ID and bank cards', '2026-05-16 10:00:00'),
(2, 'NU-1002', '2024-1005678', 'Engineering Building', '2026-05-14', 'Electronics/Gadgets', 'Blue Samsung earbuds in white charging case', '2026-05-15 09:30:00');

-- --------------------------------------------------------
-- Sample Data - found
-- --------------------------------------------------------

INSERT INTO found (FoundID, StudentNumber, Location, DateFound, Category, Description, Status, DateReported) VALUES
(1, '2024-1009012', 'Library Area', '2026-05-15', 'Wallet/Credit Card/Money', 'Found black wallet near circulation desk with ID inside', 'Unclaimed', '2026-05-16 11:00:00'),
(2, '2024-1003456', 'Engineering Wing', '2026-05-14', 'Electronics/Gadgets', 'Found blue earbuds in white case on desk in hallway', 'Unclaimed', '2026-05-15 10:15:00');

COMMIT;

-- After import, run (separately in phpMyAdmin):
--   database/history_match_group.sql  (if upgrading an older DB)
--   database/stored_procedures.sql
--   database/triggers.sql

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;