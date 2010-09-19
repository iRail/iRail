-- phpMyAdmin SQL Dump
-- version 3.2.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 19, 2010 at 07:24 PM
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
  `time` varchar(40) NOT NULL COMMENT 'date in rfc2822 format',
  `useragent` varchar(200) default NULL COMMENT 'browser info',
  `fromstation` varchar(25) NOT NULL COMMENT 'from',
  `tostation` varchar(25) NOT NULL COMMENT 'to',
  `errors` varchar(100) default 'none' COMMENT 'Error field',
  `ip` varchar(20) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `apilog`
--

INSERT INTO `apilog` VALUES(1, 'Sun, 19 Sep 2010 17:19:56 +0000', 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.472.62 Safari/534.3', 'Brugge', 's gravenbla ', 'none', NULL);
INSERT INTO `apilog` VALUES(2, 'Sun, 19 Sep 2010 19:20:30 +0200', 'Java0', 'Bruxelles-Midi', 'Liege-Guillemins', 'none', NULL);
INSERT INTO `apilog` VALUES(3, 'Sun, 19 Sep 2010 17:22:00 +0000', 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.472.62 Safari/534.3', 'Brugge', 's gravenbla ', 'none', NULL);
