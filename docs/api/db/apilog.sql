-- phpMyAdmin SQL Dump
-- version 2.11.8.1deb5+lenny5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 02, 2010 at 06:52 PM
-- Server version: 5.0.51
-- PHP Version: 5.2.6-1+lenny9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `irail`
--

-- --------------------------------------------------------

--
-- Table structure for table `apilog`
--

CREATE TABLE IF NOT EXISTS `apilog` (
  `id` int(11) NOT NULL auto_increment,
  `time` varchar(40) NOT NULL COMMENT 'date in rfc2822 format',
  `useragent` varchar(200) default NULL COMMENT 'browser info',
  `fromstation` varchar(25) NOT NULL COMMENT 'from',
  `tostation` varchar(25) NOT NULL COMMENT 'to',
  `errors` varchar(100) default 'none' COMMENT 'Error field',
  `ip` varchar(20) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `apilog`
--

