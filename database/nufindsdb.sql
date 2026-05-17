-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2026 at 10:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nufindsdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `found`
--

CREATE TABLE `found` (
  `FoundID` int(11) NOT NULL,
  `StudentNumber` varchar(20) NOT NULL,
  `Location` varchar(255) NOT NULL,
  `DateFound` date NOT NULL,
  `Category` enum('Wallet/Credit Card/Money','Identity Document','Bag','Electronics','Accessories','Others') NOT NULL,
  `Description` text NOT NULL,
  `Status` varchar(20) DEFAULT 'Unclaimed',
  `DateReported` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `HistoryID` int(11) NOT NULL,
  `ReportType` enum('Lost','Found') NOT NULL,
  `OriginalReportID` int(11) NOT NULL,
  `TicketNumber` varchar(10) DEFAULT NULL,
  `StudentNumber` varchar(20) NOT NULL,
  `Location` varchar(255) NOT NULL,
  `ReportDate` date NOT NULL,
  `Category` varchar(100) NOT NULL,
  `Description` text NOT NULL,
  `FinalStatus` varchar(50) NOT NULL,
  `DateCompleted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lost`
--

CREATE TABLE `lost` (
  `LostID` int(11) NOT NULL,
  `TicketNumber` varchar(10) NOT NULL,
  `StudentNumber` varchar(20) NOT NULL,
  `Location` varchar(255) NOT NULL,
  `DateLost` date NOT NULL,
  `Category` enum('Wallet/Credit Card/Money','Identity Document','Bag','Electronics','Accessories','Others') NOT NULL,
  `Description` text NOT NULL,
  `DateReported` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studentinfo`
--

CREATE TABLE `studentinfo` (
  `StudentNumber` varchar(20) NOT NULL,
  `CollegeDepartment` enum('COLLEGE OF ALLIED HEALTH','COLLEGE OF ARCHITECTURE','COLLEGE OF BUSINESS AND ACCOUNTANCY','COLLEGE OF COMPUTING AND INFORMATION TECHNOLOGIES','COLLEGE OF EDUCATION ARTS AND SCIENCES','COLLEGE OF ENGINEERING','COLLEGE OF TOURISM AND HOSPITALITY MANAGEMENT') NOT NULL,
  `StudentEmail` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `found`
--
ALTER TABLE `found`
  ADD PRIMARY KEY (`FoundID`),
  ADD KEY `StudentNumber` (`StudentNumber`);

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`HistoryID`);

--
-- Indexes for table `lost`
--
ALTER TABLE `lost`
  ADD PRIMARY KEY (`LostID`),
  ADD UNIQUE KEY `TicketNumber` (`TicketNumber`),
  ADD KEY `StudentNumber` (`StudentNumber`);

--
-- Indexes for table `studentinfo`
--
ALTER TABLE `studentinfo`
  ADD PRIMARY KEY (`StudentNumber`),
  ADD UNIQUE KEY `UNIQUE` (`StudentEmail`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `found`
--
ALTER TABLE `found`
  MODIFY `FoundID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `HistoryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lost`
--
ALTER TABLE `lost`
  MODIFY `LostID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `found`
--
ALTER TABLE `found`
  ADD CONSTRAINT `found_ibfk_1` FOREIGN KEY (`StudentNumber`) REFERENCES `studentinfo` (`StudentNumber`);

--
-- Constraints for table `lost`
--
ALTER TABLE `lost`
  ADD CONSTRAINT `lost_ibfk_1` FOREIGN KEY (`StudentNumber`) REFERENCES `studentinfo` (`StudentNumber`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
