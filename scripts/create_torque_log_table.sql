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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `debug_log`
--

CREATE TABLE `debug_log` (
  `idx` bigint(20) NOT NULL,
  `time` bigint(20) NOT NULL,
  `request` varchar(2048) NOT NULL,
  `queries` varchar(4096) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `defaultunit`
--

CREATE TABLE `defaultunit` (
  `idx` int(11) NOT NULL,
  `v` varchar(1) NOT NULL,
  `session` varchar(15) NOT NULL,
  `id` varchar(32) NOT NULL,
  `eml` varchar(64) NOT NULL,
  `time` varchar(15) NOT NULL,
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stand-in structure for view `monthly_totals`
-- (See below for the actual view)
--
CREATE TABLE `monthly_totals` (
`date_formatted` varchar(6)
,`distance` double
);

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `permalink_log`
--

CREATE TABLE `permalink_log` (
  `permalink_id` char(64) NOT NULL,
  `time` bigint(20) NOT NULL,
  `remote_addr` varchar(64) NOT NULL,
  `remote_details` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE `profile` (
  `idx` int(11) NOT NULL,
  `v` int(1) NOT NULL,
  `session` bigint(15) NOT NULL,
  `id` char(32) NOT NULL,
  `eml` varchar(64) NOT NULL,
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
  `SessionFuel` float NOT NULL DEFAULT '0' COMMENT 'Fuel quantity in ml'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `raw_logs`
--

CREATE TABLE `raw_logs` (
  `idx` int(11) NOT NULL,
  `v` int(1) NOT NULL,
  `session` bigint(15) NOT NULL,
  `id` char(32) NOT NULL,
  `time` bigint(15) NOT NULL,
  `eml` varchar(255) DEFAULT NULL,
  `kff1005` double NOT NULL DEFAULT '0' COMMENT 'GPS Longitude',
  `kff1006` double NOT NULL DEFAULT '0' COMMENT 'GPS Latitude',
  `kff1001` float NOT NULL DEFAULT '0' COMMENT 'Speed (GPS)',
  `kff1007` float NOT NULL DEFAULT '0' COMMENT 'GPS Bearing',
  `k11` float NOT NULL DEFAULT '0' COMMENT 'Throttle Position',
  `k5` float NOT NULL DEFAULT '0' COMMENT 'Engine Coolant Temp',
  `kc` float NOT NULL DEFAULT '0' COMMENT 'Engine RPM',
  `kd` float NOT NULL DEFAULT '0' COMMENT 'Speed (OBD)',
  `kf` float NOT NULL DEFAULT '0' COMMENT 'Intake Air Temp',
  `kff1226` float NOT NULL DEFAULT '0' COMMENT 'Horsepower',
  `kff1220` float NOT NULL DEFAULT '0' COMMENT 'Accel (X)',
  `kff1221` float NOT NULL DEFAULT '0' COMMENT 'Accel (Y)',
  `k46` float NOT NULL DEFAULT '0' COMMENT 'Ambient Air Temp',
  `kff1270` float NOT NULL DEFAULT '0' COMMENT 'Barometer (on Android device)',
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(11) NOT NULL,
  `name` varchar(60) NOT NULL,
  `pass` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `userunit`
--

CREATE TABLE `userunit` (
  `idx` int(11) NOT NULL,
  `v` varchar(1) NOT NULL,
  `session` bigint(15) NOT NULL,
  `id` varchar(32) NOT NULL,
  `eml` varchar(64) NOT NULL,
  `time` varchar(15) NOT NULL,
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`idx`),
  ADD KEY `geo2` (`Latitude`,`Longitude`),
  ADD SPATIAL KEY `geo` (`GeoLoc`);

--
-- Indexes for table `debug_log`
--
ALTER TABLE `debug_log`
  ADD PRIMARY KEY (`idx`);

--
-- Indexes for table `defaultunit`
--
ALTER TABLE `defaultunit`
  ADD PRIMARY KEY (`idx`),
  ADD UNIQUE KEY `idx` (`idx`),
  ADD KEY `session` (`session`,`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `permalink`
--
ALTER TABLE `permalink`
  ADD UNIQUE KEY `permalink` (`permalink_id`);

--
-- Indexes for table `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`idx`),
  ADD UNIQUE KEY `sessionu` (`session`);

--
-- Indexes for table `raw_logs`
--
ALTER TABLE `raw_logs`
  ADD PRIMARY KEY (`idx`),
  ADD UNIQUE KEY `sessionu` (`session`,`id`,`time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`);

--
-- Indexes for table `userunit`
--
ALTER TABLE `userunit`
  ADD PRIMARY KEY (`idx`),
  ADD UNIQUE KEY `sessionu` (`session`);

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
-- AUTO_INCREMENT for table `defaultunit`
--
ALTER TABLE `defaultunit`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profile`
--
ALTER TABLE `profile`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `raw_logs`
--
ALTER TABLE `raw_logs`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `userunit`
--
ALTER TABLE `userunit`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
