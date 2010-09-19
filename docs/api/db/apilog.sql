-- phpMyAdmin SQL Dump
-- version 3.2.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 19, 2010 at 06:22 PM
-- Server version: 5.0.84
-- PHP Version: 5.3.3-pl0-gentoo

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

DROP TABLE IF EXISTS `apilog`;
CREATE TABLE `apilog` (
  `id` int(11) NOT NULL auto_increment,
  `time` varchar(40) NOT NULL,
  `useragent` varchar(100) default NULL,
  `fromstation` varchar(25) NOT NULL,
  `tostation` varchar(25) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `apilog`
--

