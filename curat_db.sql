-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2016 at 08:17 AM
-- Server version: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `curat_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE IF NOT EXISTS `doctor` (
  `email` varchar(50) NOT NULL,
  `password` char(40) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Type` varchar(50) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`email`, `password`, `Name`, `Type`) VALUES
('dr@max.com', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 'Dr Waheed Zaman', 'Specialist : Urology');

-- --------------------------------------------------------

--
-- Table structure for table `doctorpatients`
--

CREATE TABLE IF NOT EXISTS `doctorpatients` (
  `doctoremail` varchar(50) NOT NULL,
  `patientuserid` char(32) NOT NULL,
  PRIMARY KEY (`doctoremail`,`patientuserid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `doctorpatients`
--

INSERT INTO `doctorpatients` (`doctoremail`, `patientuserid`) VALUES
('dr@max.com', 'cc338e80f45ab38bbc48a604e312bf30');

-- --------------------------------------------------------

--
-- Table structure for table `organization`
--

CREATE TABLE IF NOT EXISTS `organization` (
  `Name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(50) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `organization`
--

INSERT INTO `organization` (`Name`, `email`, `password`) VALUES
('STAR Health Insurance', 'user@starhealth.com', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8');

-- --------------------------------------------------------

--
-- Table structure for table `organizationpatients`
--

CREATE TABLE IF NOT EXISTS `organizationpatients` (
  `orgemail` varchar(100) NOT NULL,
  `patientuserid` char(32) NOT NULL,
  PRIMARY KEY (`orgemail`,`patientuserid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE IF NOT EXISTS `patient` (
  `UserId` char(32) NOT NULL,
  `Code` varchar(4) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `DOB` date NOT NULL,
  `Mobile` varchar(10) NOT NULL,
  `Address` text NOT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `BloodGroup` varchar(3) NOT NULL,
  PRIMARY KEY (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`UserId`, `Code`, `Name`, `DOB`, `Mobile`, `Address`, `Email`, `BloodGroup`) VALUES
('694b184aba8933f577b97e702ae05222', '3719', 'Vishwesh Jainkuniya', '1998-07-03', '9571355996', 'Xyz gali, Kota,\r\nRajasthan', NULL, 'B+'),
('cc338e80f45ab38bbc48a604e312bf30', '6535', 'Ankur Arora', '2016-12-21', '9910188803', 'FB-5 Tagore Garden,\r\nNew Delhi', 'ankursmooth@gmail.com', 'B+');

-- --------------------------------------------------------

--
-- Table structure for table `patienthistory`
--

CREATE TABLE IF NOT EXISTS `patienthistory` (
  `UserId` varchar(32) NOT NULL,
  `DateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DoctorName` varchar(25) NOT NULL,
  `Keypoints` text NOT NULL,
  `HasAttachments` char(1) NOT NULL DEFAULT '0',
  `Details` text NOT NULL,
  `Attachments` text,
  PRIMARY KEY (`UserId`,`DateTime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `patienthistory`
--

INSERT INTO `patienthistory` (`UserId`, `DateTime`, `DoctorName`, `Keypoints`, `HasAttachments`, `Details`, `Attachments`) VALUES
('cc338e80f45ab38bbc48a604e312bf30', '2016-10-22 22:45:10', 'Dr. Waheed Zaman', 'Stones in both Kidneys.\r\nleft kidney and ureter stones removed via RIRS.\r\nStent present in left ureter.\r\nAdvised low sodium and low oxalate diet', '1', 'The patient had pain in left flank and after ultrasound and CT scan it was found that there are stones in left and right kidney. The pain is being cause by the one stuck in ureter.  \r\nStone in Ureter has been removed using RIRS and stent has been placed.\r\nAdvised to get stent removed after 2 weeks.\r\nLow sodium diet and decrease in consumption of oxalate rich foods advised.', 'https://www.softchalk.com/lessonchallenge09/lesson/Pharmacology/MC-pharmacy_medsManager_winter07_rxproof_1.jpg'),
('cc338e80f45ab38bbc48a604e312bf30', '2016-10-22 22:47:10', 'Dr. Baba', 'Loose Motion due to anxiety\r\nGiven Alprax', '1', 'The patient''s intestines get irritated due to anxiety leading to lose motions, patient has been given anti anxiety.', '/attachments/54d27c246adf430a846d0b4f3f435848.jpg'),
('cc338e80f45ab38bbc48a604e312bf30', '2016-10-23 02:39:55', 'Dr Waheed Zaman', 'k e y p oi nts', '0', 'orem ipsum dolor sit amet, te est ocurreret deterruisset, ex sed altera audire vituperatoribus, vim tota sapientem reprehendunt at. Has id erat possit volutpat. Utamur scaevola accusamus cu nam. Ad albucius omnesque petentium vim, id euripidis neglegentur pri.  Vero everti elaboraret sit an. An sint molestie ponderum his. Usu ad altera vocibus epicurei. Ullum nulla et mea, omittam blandit interesset ad sea. Cum adolescens scripserit id, mundi tantas pri ut. Duo ad aliquam invidunt partiendo. Tollit patrioque at est.', NULL);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
