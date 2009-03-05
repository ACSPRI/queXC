-- phpMyAdmin SQL Dump
-- version 2.11.2.2
-- http://www.phpmyadmin.net
--
-- Host: databasedev.dcarf
-- Generation Time: Feb 19, 2009 at 03:51 PM
-- Server version: 5.0.67
-- PHP Version: 5.2.6-2ubuntu4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `quexc`
--

-- --------------------------------------------------------

--
-- Table structure for table `cell`
--

CREATE TABLE `cell` (
  `cell_id` bigint(20) NOT NULL auto_increment,
  `row_id` bigint(20) NOT NULL,
  `column_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`cell_id`),
  UNIQUE KEY `row_id_2` (`row_id`,`column_id`),
  KEY `row_id` (`row_id`),
  KEY `column_id` (`column_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `cell`
--


-- --------------------------------------------------------

--
-- Table structure for table `cell_revision`
--

CREATE TABLE `cell_revision` (
  `cell_revision_id` bigint(20) NOT NULL auto_increment,
  `cell_id` bigint(20) NOT NULL,
  `work_unit_id` bigint(20) default NULL,
  `data` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`cell_revision_id`),
  KEY `cell_id` (`cell_id`),
  KEY `work_unit_id` (`work_unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `cell_revision`
--


-- --------------------------------------------------------

--
-- Table structure for table `code`
--

CREATE TABLE `code` (
  `code_id` bigint(20) NOT NULL auto_increment,
  `value` varchar(255) collate utf8_unicode_ci NOT NULL,
  `label` text collate utf8_unicode_ci NOT NULL,
  `keywords` text collate utf8_unicode_ci,
  `code_level_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`code_id`),
  KEY `code_level_id` (`code_level_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `code`
--

INSERT INTO `code` VALUES(1, '1', 'Blank', '', 1);
INSERT INTO `code` VALUES(2, '2', 'Not codeable', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `code_group`
--

CREATE TABLE `code_group` (
  `code_group_id` bigint(20) NOT NULL auto_increment,
  `description` text collate utf8_unicode_ci NOT NULL,
  `blank_code_id` bigint(20) default NULL COMMENT 'The code_id within this group to assign to blank records',
  `allow_additions` tinyint(1) NOT NULL default '0' COMMENT '0 for no additions, 1 for additions allowed',
  `in_input` tinyint(1) NOT NULL default '1' COMMENT '0 for a manually imported code, 1 for imported from a data file',
  PRIMARY KEY  (`code_group_id`),
  KEY `blank_code_id` (`blank_code_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `code_group`
--

INSERT INTO `code_group` VALUES(1, 'Blank', 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `code_level`
--

CREATE TABLE `code_level` (
  `code_level_id` bigint(20) NOT NULL auto_increment,
  `code_group_id` bigint(20) NOT NULL,
  `level` int(11) NOT NULL default '0',
  `width` int(11) NOT NULL,
  PRIMARY KEY  (`code_level_id`),
  KEY `code_group_id` (`code_group_id`),
  KEY `level` (`level`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `code_level`
--

INSERT INTO `code_level` VALUES(1, 1, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `code_parent`
--

CREATE TABLE `code_parent` (
  `code_id` bigint(20) NOT NULL,
  `parent_code_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`code_id`,`parent_code_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `code_parent`
--


-- --------------------------------------------------------

--
-- Table structure for table `column`
--

CREATE TABLE `column` (
  `column_id` bigint(20) NOT NULL auto_increment,
  `data_id` bigint(20) NOT NULL,
  `column_group_id` bigint(20) NOT NULL,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `description` text collate utf8_unicode_ci NOT NULL,
  `startpos` int(11) default NULL COMMENT 'Location in fixed width file of start of cell',
  `width` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL default '0' COMMENT '0 for integer, 1 for character',
  `in_input` tinyint(1) NOT NULL default '0' COMMENT 'Set to 1 if this column exists in the input file',
  `sortorder` int(11) NOT NULL default '0' COMMENT 'Sort order in output',
  `code_level_id` bigint(20) default NULL,
  PRIMARY KEY  (`column_id`),
  KEY `name` (`name`),
  KEY `column_group_id` (`column_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `column`
--


-- --------------------------------------------------------

--
-- Table structure for table `column_group`
--

CREATE TABLE `column_group` (
  `column_group_id` bigint(20) NOT NULL auto_increment,
  `description` text collate utf8_unicode_ci NOT NULL,
  `code_group_id` bigint(20) default NULL,
  PRIMARY KEY  (`column_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Description for a column group (equivalent of a var group in';

--
-- Dumping data for table `column_group`
--


-- --------------------------------------------------------

--
-- Table structure for table `column_process_column`
--

CREATE TABLE `column_process_column` (
  `process_id` bigint(20) NOT NULL,
  `column_id` bigint(20) NOT NULL,
  `relevant_column_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`process_id`,`column_id`,`relevant_column_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Display these columns when using this process on this column';

--
-- Dumping data for table `column_process_column`
--


-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE `data` (
  `data_id` bigint(20) NOT NULL auto_increment,
  `description` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `data`
--


-- --------------------------------------------------------

--
-- Table structure for table `operator`
--

CREATE TABLE `operator` (
  `operator_id` bigint(20) NOT NULL auto_increment,
  `description` text collate utf8_unicode_ci NOT NULL,
  `username` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`operator_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `operator`
--


-- --------------------------------------------------------

--
-- Table structure for table `operator_data`
--

CREATE TABLE `operator_data` (
  `operator_id` bigint(20) NOT NULL,
  `data_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`operator_id`,`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `operator_data`
--


-- --------------------------------------------------------

--
-- Table structure for table `operator_process`
--

CREATE TABLE `operator_process` (
  `operator_id` bigint(20) NOT NULL,
  `process_id` bigint(20) NOT NULL,
  `auto_code` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`operator_id`,`process_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `operator_process`
--


-- --------------------------------------------------------

--
-- Table structure for table `process`
--

CREATE TABLE `process` (
  `process_id` bigint(20) NOT NULL auto_increment,
  `description` text collate utf8_unicode_ci NOT NULL,
  `code_group_id` bigint(20) default NULL,
  `auto_process_function` varchar(255) collate utf8_unicode_ci default NULL,
  `manual_process_function` varchar(255) collate utf8_unicode_ci default NULL,
  `auto_code_value` tinyint(1) NOT NULL default '0',
  `auto_code_label` tinyint(1) NOT NULL default '0',
  `template` tinyint(1) NOT NULL default '0' COMMENT 'Use as a template',
  PRIMARY KEY  (`process_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `process`
--

INSERT INTO `process` VALUES(1, 'Spacing', NULL, 'spacing', 'spacing_display', 0, 0, 0);
INSERT INTO `process` VALUES(2, 'Spell check', NULL, 'spelling', 'spelling_display', 0, 0, 0);
INSERT INTO `process` VALUES(3, 'Email validate', NULL, 'email', 'email_display', 0, 0, 0);
INSERT INTO `process` VALUES(4, 'Create new code group', 1, NULL, NULL, 0, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `process_parent`
--

CREATE TABLE `process_parent` (
  `process_id` bigint(20) NOT NULL,
  `parent_process_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`process_id`,`parent_process_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `process_parent`
--

INSERT INTO `process_parent` VALUES(2, 1);
INSERT INTO `process_parent` VALUES(3, 1);
INSERT INTO `process_parent` VALUES(4, 2);

-- --------------------------------------------------------

--
-- Table structure for table `work`
--

CREATE TABLE `work` (
  `work_id` bigint(20) NOT NULL auto_increment,
  `column_id` bigint(20) NOT NULL COMMENT 'Column from',
  `column_group_id` bigint(20) default NULL COMMENT 'The column group where we are sending the codes to',
  `process_id` bigint(20) NOT NULL COMMENT 'The process to apply',
  `operator_id` bigint(20) default NULL COMMENT 'Assign this job to a specific operator',
  PRIMARY KEY  (`work_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `work`
--


-- --------------------------------------------------------

--
-- Table structure for table `work_parent`
--

CREATE TABLE `work_parent` (
  `work_id` bigint(20) NOT NULL,
  `parent_work_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`work_id`,`parent_work_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `work_parent`
--


-- --------------------------------------------------------

--
-- Table structure for table `work_unit`
--

CREATE TABLE `work_unit` (
  `work_unit_id` bigint(20) NOT NULL auto_increment,
  `work_id` bigint(20) NOT NULL,
  `cell_id` bigint(20) NOT NULL,
  `process_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) default NULL,
  `assigned` datetime default NULL,
  `completed` datetime default NULL,
  PRIMARY KEY  (`work_unit_id`),
  KEY `process_id` (`process_id`),
  KEY `cell_id` (`cell_id`),
  KEY `work_id` (`work_id`),
  KEY `operator_id` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `work_unit`
--

