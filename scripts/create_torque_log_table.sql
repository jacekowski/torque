USE `torque`;


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `torque`
--

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `idx` bigint(20) NOT NULL,
  `Country` char(2) DEFAULT NULL,
  `City` tinytext,
  `AccentCity` tinytext,
  `Latitude` float NOT NULL,
  `Longitude` float NOT NULL,
  `GeoLoc` point NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `debug_log`
--

CREATE TABLE `debug_log` (
  `idx` bigint(20) NOT NULL,
  `time` bigint(20) NOT NULL,
  `version` varchar(16) NOT NULL DEFAULT '0',
  `request` varchar(2048) NOT NULL,
  `queries` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `defaultunit_v2`
--

CREATE TABLE `defaultunit_v2` (
  `session` bigint(20) NOT NULL,
  `pid` int(11) NOT NULL,
  `unit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `permalink`
--

CREATE TABLE `permalink` (
  `permalink_id` char(64) NOT NULL,
  `time` bigint(20) NOT NULL,
  `session` varchar(15) NOT NULL,
  `variables` varchar(4096) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `permalink_log`
--

CREATE TABLE `permalink_log` (
  `permalink_id` char(64) NOT NULL,
  `time` bigint(20) NOT NULL,
  `remote_addr` varchar(64) NOT NULL,
  `remote_details` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `profile_v2`
--

CREATE TABLE `profile_v2` (
  `idx` int(11) NOT NULL,
  `v` int(1) NOT NULL,
  `session` bigint(15) NOT NULL,
  `id` char(32) NOT NULL,
  `eml` varchar(256) NOT NULL,
  `time` bigint(15) NOT NULL,
  `profileName` varchar(32) NOT NULL DEFAULT '0' COMMENT 'Profile Name',
  `profileFuelCost` float NOT NULL DEFAULT '0' COMMENT 'Fuel Cost',
  `profileFuelType` float NOT NULL DEFAULT '0' COMMENT 'Fuel Type',
  `profileVe` float NOT NULL DEFAULT '0' COMMENT 'Volumetric Efficiency Percent',
  `profileWeight` float NOT NULL DEFAULT '0' COMMENT 'Vehicle Weight',
  `MinTime` bigint(20) NOT NULL DEFAULT '0',
  `MaxTime` bigint(20) NOT NULL DEFAULT '0',
  `SessionSize` bigint(20) NOT NULL DEFAULT '0',
  `SessionDistance` float NOT NULL DEFAULT '0',
  `SessionFuel` float NOT NULL DEFAULT '0' COMMENT 'Fuel quantity in ml',
  `CityStart` bigint(20) NOT NULL DEFAULT '0',
  `CityEnd` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `raw_logs_v2`
--

CREATE TABLE `raw_logs_v2` (
  `session` bigint(13) NOT NULL,
  `time` bigint(13) NOT NULL,
  `pid` int(8) NOT NULL,
  `value` decimal(21,10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(11) NOT NULL,
  `name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `userunit_v2`
--

CREATE TABLE `userunit_v2` (
  `session` bigint(20) NOT NULL,
  `pid` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `fullName` varchar(50) DEFAULT NULL,
  `shortName` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `uid` int(11) NOT NULL,
  `parameter` varchar(60) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`idx`),
  ADD KEY `geo2` (`Latitude`,`Longitude`),
  ADD SPATIAL KEY `geo` (`GeoLoc`),
  ADD KEY `country` (`Country`) USING BTREE;

--
-- Indexes for table `debug_log`
--
ALTER TABLE `debug_log`
  ADD PRIMARY KEY (`idx`);

--
-- Indexes for table `defaultunit_v2`
--
ALTER TABLE `defaultunit_v2`
  ADD UNIQUE KEY `session` (`session`);

--
-- Indexes for table `permalink`
--
ALTER TABLE `permalink`
  ADD UNIQUE KEY `permalink` (`permalink_id`);

--
-- Indexes for table `profile_v2`
--
ALTER TABLE `profile_v2`
  ADD PRIMARY KEY (`idx`),
  ADD UNIQUE KEY `sessionu` (`session`),
  ADD KEY `eml` (`eml`);

--
-- Indexes for table `raw_logs_v2`
--
ALTER TABLE `raw_logs_v2`
  ADD UNIQUE KEY `sess` (`session`,`time`,`pid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `userunit_v2`
--
ALTER TABLE `userunit_v2`
  ADD UNIQUE KEY `spid` (`session`,`pid`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD UNIQUE KEY `uid-param` (`uid`,`parameter`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `idx` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `debug_log`
--
ALTER TABLE `debug_log`
  MODIFY `idx` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profile_v2`
--
ALTER TABLE `profile_v2`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
