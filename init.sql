SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `erepbot` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `erepbot`;

CREATE TABLE `binding` (
  `ircid` varchar(50) NOT NULL,
  `name` varchar(40) NOT NULL,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `create_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `id_cache` (
  `id` int(10) NOT NULL,
  `name` varchar(40) NOT NULL,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `create_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `binding`
  ADD PRIMARY KEY (`ircid`),
  ADD KEY `update_time` (`update_time`);

ALTER TABLE `id_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `update_time` (`update_time`);

