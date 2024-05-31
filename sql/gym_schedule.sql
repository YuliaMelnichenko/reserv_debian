-- phpMyAdmin SQL Dump
-- version 3.2.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 31, 2024 at 04:16 PM
-- Server version: 5.1.40
-- PHP Version: 5.2.12

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `TORI`
--

-- --------------------------------------------------------

--
-- Table structure for table `gym_schedule`
--

CREATE TABLE IF NOT EXISTS `gym_schedule` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `USERID` int(6) NOT NULL,
  `DATE_TRAIN` date NOT NULL,
  `START_TIME` time NOT NULL,
  `STOP_TIME` time NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=25 ;

--
-- Dumping data for table `gym_schedule`
--

INSERT INTO `gym_schedule` (`ID`, `USERID`, `DATE_TRAIN`, `START_TIME`, `STOP_TIME`) VALUES
(21, 31, '2024-06-11', '19:00:00', '20:00:00'),
(3, 2, '2024-04-18', '13:00:00', '14:00:00'),
(2, 2, '2024-04-16', '13:00:00', '14:00:00'),
(20, 31, '2024-06-06', '19:00:00', '20:00:00'),
(19, 31, '2024-06-04', '19:00:00', '20:00:00'),
(22, 31, '2024-06-13', '19:00:00', '20:00:00'),
(18, 31, '2024-05-30', '19:00:00', '20:00:00');
