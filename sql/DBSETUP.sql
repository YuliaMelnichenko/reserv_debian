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
-- Table structure for table `DBSETUP`
--

CREATE TABLE IF NOT EXISTS `DBSETUP` (
  `paramName` varchar(255) NOT NULL,
  `valueInt` int(11) NOT NULL DEFAULT '0',
  `valueFloat` float NOT NULL DEFAULT '0',
  `valueStr` varchar(255) NOT NULL DEFAULT '',
  `comment` varchar(512) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `DBSETUP`
--

INSERT INTO `DBSETUP` (`paramName`, `valueInt`, `valueFloat`, `valueStr`, `comment`) VALUES
('add_time_journal_deep_day', 180, 0, '', ''),
('delay_journal_deep_day', 180, 0, '', ''),
('pause_journal_deep_day', 180, 0, '', '');
