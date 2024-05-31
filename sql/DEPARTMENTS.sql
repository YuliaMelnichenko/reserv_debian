-- phpMyAdmin SQL Dump
-- version 3.2.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 31, 2024 at 04:14 PM
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
-- Table structure for table `DEPARTMENTS`
--

CREATE TABLE IF NOT EXISTS `DEPARTMENTS` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(1024) NOT NULL,
  `ROOM` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `DEPARTMENTS`
--

INSERT INTO `DEPARTMENTS` (`ID`, `NAME`, `ROOM`) VALUES
(1, 'ОА', '501'),
(2, 'ТО', '556'),
(20, 'СТО', '201'),
(3, 'Бухгалтерия', '202'),
(10, 'ОА (г. Оренбург)', 'ARMADA'),
(11, 'ТО (г. Оренбург)', 'ARMADA'),
(25, 'СР', '207');
