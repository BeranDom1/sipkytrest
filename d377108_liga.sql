-- phpMyAdmin SQL Dump
-- version 3.5.8.2
-- http://www.phpmyadmin.net
--
-- Počítač: md418.wedos.net:3306
-- Vygenerováno: Pon 09. úno 2026, 09:07
-- Verze serveru: 10.4.34-MariaDB-log
-- Verze PHP: 5.4.23

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databáze: `d377108_liga`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=4 ;

--
-- Vypisuji data pro tabulku `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(2, 'admin', '$2y$10$NYVKFQaBcPzE5wBSgkfSzeydSGO/hk3NFYH/AOPNgovcqs4RUuKgK', '2025-08-26 15:34:29');

-- --------------------------------------------------------

--
-- Struktura tabulky `backup_hraci_unikatni_jmena`
--

CREATE TABLE IF NOT EXISTS `backup_hraci_unikatni_jmena` (
  `libovolne_id` int(11),
  `jmeno` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

--
-- Vypisuji data pro tabulku `backup_hraci_unikatni_jmena`
--

INSERT INTO `backup_hraci_unikatni_jmena` (`libovolne_id`, `jmeno`) VALUES
(21, 'Adam Krchňavý'),
(36, 'Adéla Krizsanová'),
(30, 'David Bartů'),
(41, 'David Roháček'),
(20, 'Dominik Beran'),
(16, 'Filip Kirchner'),
(44, 'Filip Matějičný'),
(5, 'Filip Vesecký'),
(15, 'František Poledna'),
(12, 'František Soukup'),
(3, 'Jakub Šebesta'),
(11, 'Jan Brussmann'),
(23, 'Jan Choboda'),
(39, 'Jan Klíma'),
(4, 'Jan Novotný'),
(34, 'Jan Vrátil'),
(40, 'Jaromír Svododa'),
(27, 'Jiří Zlatuška'),
(7, 'Ladislav Popelář'),
(119, 'Leoš Chromý'),
(18, 'Libor Penička'),
(28, 'Luboš Štěpnička'),
(14, 'Lukáš Chromý'),
(29, 'Lukáš Karkula'),
(22, 'Martin Bednář'),
(42, 'Michal Mlynarček'),
(33, 'Michal Rosa'),
(8, 'Michal Šimek'),
(6, 'Michal Tausch'),
(9, 'Míra Kratochvíl'),
(10, 'Miroslav Moravský'),
(32, 'Míša Beranová'),
(43, 'Monika Žiačíková'),
(31, 'Pavel Čapoun'),
(26, 'Pavel Kubala'),
(2, 'Pavel Svoboda'),
(45, 'Pepa Herkner'),
(25, 'Petr Císař'),
(19, 'Petr Foitl'),
(130, 'Petr Kopecký'),
(13, 'Radek Ťápal'),
(137, 'Radim Pittner'),
(24, 'Roman Caha'),
(1, 'Standa Holoubek'),
(38, 'Tomáš Hlavatý'),
(17, 'Tonda Sobotka'),
(35, 'Vojta Novotný'),
(37, 'Zdeněk Machala');

-- --------------------------------------------------------

--
-- Struktura tabulky `hraci`
--

CREATE TABLE IF NOT EXISTS `hraci` (
  `id` int(11) NOT NULL,
  `jmeno` varchar(100) DEFAULT NULL,
  `liga_id` int(11) DEFAULT NULL,
  `rocnik_id` int(11) DEFAULT NULL,
  `z` int(11) DEFAULT 0,
  `v` int(11) DEFAULT 0,
  `p` int(11) DEFAULT 0,
  `rzd` int(11) DEFAULT 0,
  `body` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_hrac_jmeno` (`jmeno`),
  KEY `liga_id` (`liga_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Vypisuji data pro tabulku `hraci`
--

INSERT INTO `hraci` (`id`, `jmeno`, `liga_id`, `rocnik_id`, `z`, `v`, `p`, `rzd`, `body`) VALUES
(1, 'Standa Holoubek', 6, 2, 0, 0, 0, 0, 0),
(2, 'Pavel Svoboda', 6, 2, 0, 0, 0, 0, 0),
(3, 'Jakub Šebesta', 6, 2, 0, 0, 0, 0, 0),
(4, 'Jan Novotný', 6, 2, 0, 0, 0, 0, 0),
(5, 'Filip Vesecký', 6, 2, 0, 0, 0, 0, 0),
(6, 'Michal Tausch', 6, 2, 0, 0, 0, 0, 0),
(7, 'Ladislav Popelář', 6, 2, 0, 0, 0, 0, 0),
(8, 'Michal Šimek', 6, 2, 0, 0, 0, 0, 0),
(9, 'Míra Kratochvíl', 6, 2, 0, 0, 0, 0, 0),
(10, 'Miroslav Moravský', 6, 2, 0, 0, 0, 0, 0),
(11, 'Jan Brussmann', 6, 2, 0, 0, 0, 0, 0),
(12, 'František Soukup', 6, 2, 0, 0, 0, 0, 0),
(13, 'Radek Ťápal', 6, 2, 0, 0, 0, 0, 0),
(14, 'Lukáš Chromý', 6, 2, 0, 0, 0, 0, 0),
(15, 'František Poledna', 6, 2, 0, 0, 0, 0, 0),
(16, 'Filip Kirchner', 6, 2, 0, 0, 0, 0, 0),
(17, 'Tonda Sobotka', 6, 2, 0, 0, 0, 0, 0),
(18, 'Libor Penička', 6, 2, 0, 0, 0, 0, 0),
(19, 'Petr Foitl', 6, 2, 0, 0, 0, 0, 0),
(20, 'Dominik Beran', 6, 2, 0, 0, 0, 0, 0),
(21, 'Adam Krchňavý', 6, 2, 0, 0, 0, 0, 0),
(22, 'Martin Bednář', 6, 2, 0, 0, 0, 0, 0),
(23, 'Jan Choboda', 6, 2, 0, 0, 0, 0, 0),
(24, 'Roman Caha', 6, 2, 0, 0, 0, 0, 0),
(25, 'Petr Císař', 6, 2, 0, 0, 0, 0, 0),
(26, 'Pavel Kubala', 6, 2, 0, 0, 0, 0, 0),
(27, 'Jiří Zlatuška', 6, 2, 0, 0, 0, 0, 0),
(28, 'Luboš Štěpnička', 6, 2, 0, 0, 0, 0, 0),
(29, 'Lukáš Karkula', 6, 2, 0, 0, 0, 0, 0),
(30, 'David Bartů', 6, 2, 0, 0, 0, 0, 0),
(31, 'Pavel Čapoun', 6, 2, 0, 0, 0, 0, 0),
(32, 'Míša Beranová', 6, 2, 0, 0, 0, 0, 0),
(33, 'Michal Rosa', 6, 2, 0, 0, 0, 0, 0),
(34, 'Jan Vrátil', 6, 2, 0, 0, 0, 0, 0),
(35, 'Vojta Novotný', 6, 2, 0, 0, 0, 0, 0),
(36, 'Adéla Krizsanová', 6, 2, 0, 0, 0, 0, 0),
(37, 'Zdeněk Machala', 6, 2, 0, 0, 0, 0, 0),
(38, 'Tomáš Hlavatý', 6, 2, 0, 0, 0, 0, 0),
(39, 'Jan Klíma', 6, 2, 0, 0, 0, 0, 0),
(40, 'Jaromír Svododa', 6, 2, 0, 0, 0, 0, 0),
(41, 'David Roháček', 6, 2, 0, 0, 0, 0, 0),
(42, 'Michal Mlynarček', 6, 2, 0, 0, 0, 0, 0),
(43, 'Monika Žiačíková', 6, 2, 0, 0, 0, 0, 0),
(44, 'Filip Matějičný', 6, 2, 0, 0, 0, 0, 0),
(45, 'Pepa Herkner', 6, 2, 0, 0, 0, 0, 0),
(46, 'Jaromír Drda', 6, 3, 0, 0, 0, 0, 0),
(48, 'Kateřina Fencíková', 6, 3, 0, 0, 0, 0, 0),
(49, 'Miroslav Fencík', 6, 3, 0, 0, 0, 0, 0),
(50, 'Ondřej Foitl', 6, 3, 0, 0, 0, 0, 0),
(51, 'Míra Svoboda', 6, 2, 0, 0, 0, 0, 0),
(52, 'Jan Popelář', 6, 3, 0, 0, 0, 0, 0),
(119, 'Leoš Chromý', 6, 1, 0, 0, 0, 0, 0),
(130, 'Petr Kopecký', 6, 1, 0, 0, 0, 0, 0),
(137, 'Radim Pittner', 6, 1, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Struktura tabulky `hraci_unikatni_jmena`
--

CREATE TABLE IF NOT EXISTS `hraci_unikatni_jmena` (
  `libovolne_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `jmeno` varchar(255) NOT NULL,
  PRIMARY KEY (`libovolne_id`),
  UNIQUE KEY `uk_jmeno` (`jmeno`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci AUTO_INCREMENT=154 ;

--
-- Vypisuji data pro tabulku `hraci_unikatni_jmena`
--

INSERT INTO `hraci_unikatni_jmena` (`libovolne_id`, `jmeno`) VALUES
(21, 'Adam Krchňavý'),
(36, 'Adéla Krizsanová'),
(30, 'David Bartů'),
(41, 'David Roháček'),
(20, 'Dominik Beran'),
(152, 'Fanda Poledna'),
(16, 'Filip Kirchner'),
(44, 'Filip Matějičný'),
(5, 'Filip Vesecký'),
(15, 'František Poledna'),
(12, 'František Soukup'),
(3, 'Jakub Šebesta'),
(11, 'Jan Brussmann'),
(23, 'Jan Choboda'),
(39, 'Jan Klíma'),
(143, 'Jan Křen'),
(144, 'Jan Matoušek'),
(4, 'Jan Novotný'),
(52, 'Jan Popelář'),
(34, 'Jan Vrátil'),
(138, 'Jára Vávrů'),
(46, 'Jaromír Drda'),
(149, 'Jářa Knotková'),
(27, 'Jiří Zlatuška'),
(148, 'Jitka Šebestová'),
(48, 'Kateřina Fencíková'),
(7, 'Ladislav Popelář'),
(119, 'Leoš Chromý'),
(18, 'Libor Penička'),
(150, 'Lída Polednová'),
(28, 'Luboš Štěpnička'),
(14, 'Lukáš Chromý'),
(29, 'Lukáš Karkula'),
(142, 'Marek Pecina'),
(22, 'Martin Bednář'),
(151, 'Martina Nováková'),
(145, 'Michal Babický'),
(42, 'Michal Mlynarček'),
(33, 'Michal Rosa'),
(8, 'Michal Šimek'),
(6, 'Michal Tausch'),
(9, 'Míra Kratochvíl'),
(40, 'Míra Svododa'),
(49, 'Miroslav Fencík'),
(10, 'Miroslav Moravský'),
(32, 'Míša Beranová'),
(146, 'Míša Pěničková'),
(43, 'Monika Žiačíková'),
(50, 'Ondřej Foitl'),
(31, 'Pavel Čapoun'),
(26, 'Pavel Kubala'),
(2, 'Pavel Svoboda'),
(45, 'Pepa Herkner'),
(25, 'Petr Císař'),
(19, 'Petr Foitl'),
(130, 'Petr Kopecký'),
(141, 'Petr Krejčí'),
(13, 'Radek Ťápal'),
(137, 'Radim Pittner'),
(24, 'Roman Caha'),
(147, 'Sabina Němcová'),
(1, 'Standa Holoubek'),
(139, 'Standa Novák'),
(153, 'Tomáš Fencík'),
(38, 'Tomáš Hlavatý'),
(17, 'Tonda Sobotka'),
(140, 'Václav Tetour'),
(35, 'Vojta Novotný'),
(37, 'Zdeněk Machala');

-- --------------------------------------------------------

--
-- Struktura tabulky `hraci_unikatni_jmena_tmp`
--

CREATE TABLE IF NOT EXISTS `hraci_unikatni_jmena_tmp` (
  `libovolne_id` int(10) unsigned NOT NULL,
  `jmeno` varchar(255) NOT NULL,
  PRIMARY KEY (`libovolne_id`),
  UNIQUE KEY `uk_jmeno` (`jmeno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Vypisuji data pro tabulku `hraci_unikatni_jmena_tmp`
--

INSERT INTO `hraci_unikatni_jmena_tmp` (`libovolne_id`, `jmeno`) VALUES
(21, 'Adam Krchňavý'),
(36, 'Adéla Krizsanová'),
(30, 'David Bartů'),
(41, 'David Roháček'),
(20, 'Dominik Beran'),
(16, 'Filip Kirchner'),
(44, 'Filip Matějičný'),
(5, 'Filip Vesecký'),
(15, 'František Poledna'),
(12, 'František Soukup'),
(3, 'Jakub Šebesta'),
(11, 'Jan Brussmann'),
(23, 'Jan Choboda'),
(39, 'Jan Klíma'),
(4, 'Jan Novotný'),
(34, 'Jan Vrátil'),
(40, 'Jaromír Svododa'),
(27, 'Jiří Zlatuška'),
(7, 'Ladislav Popelář'),
(119, 'Leoš Chromý'),
(18, 'Libor Penička'),
(28, 'Luboš Štěpnička'),
(14, 'Lukáš Chromý'),
(29, 'Lukáš Karkula'),
(22, 'Martin Bednář'),
(42, 'Michal Mlynarček'),
(33, 'Michal Rosa'),
(8, 'Michal Šimek'),
(6, 'Michal Tausch'),
(9, 'Míra Kratochvíl'),
(10, 'Miroslav Moravský'),
(32, 'Míša Beranová'),
(43, 'Monika Žiačíková'),
(31, 'Pavel Čapoun'),
(26, 'Pavel Kubala'),
(2, 'Pavel Svoboda'),
(45, 'Pepa Herkner'),
(25, 'Petr Císař'),
(19, 'Petr Foitl'),
(130, 'Petr Kopecký'),
(13, 'Radek Ťápal'),
(137, 'Radim Pittner'),
(24, 'Roman Caha'),
(1, 'Standa Holoubek'),
(38, 'Tomáš Hlavatý'),
(17, 'Tonda Sobotka'),
(35, 'Vojta Novotný'),
(37, 'Zdeněk Machala');

-- --------------------------------------------------------

--
-- Struktura tabulky `hraci_v_sezone`
--

CREATE TABLE IF NOT EXISTS `hraci_v_sezone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrac_id` int(11) unsigned NOT NULL,
  `rocnik_id` int(11) NOT NULL,
  `liga_id` int(11) NOT NULL,
  `poznamka` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_hrac_rocnik` (`hrac_id`,`rocnik_id`),
  KEY `idx_rocnik_liga` (`rocnik_id`,`liga_id`),
  KEY `fk_hs_liga` (`liga_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=879 ;

--
-- Vypisuji data pro tabulku `hraci_v_sezone`
--

INSERT INTO `hraci_v_sezone` (`id`, `hrac_id`, `rocnik_id`, `liga_id`, `poznamka`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:38:34'),
(2, 2, 2, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:38:34'),
(3, 3, 2, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:38:34'),
(4, 5, 2, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:38:34'),
(5, 7, 2, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:38:34'),
(6, 9, 2, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:38:34'),
(7, 4, 2, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:38:34'),
(8, 6, 2, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:38:34'),
(9, 8, 2, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:38:34'),
(10, 10, 2, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 05:08:26'),
(11, 11, 2, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 05:09:09'),
(12, 12, 2, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 05:09:13'),
(13, 13, 2, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 05:09:18'),
(14, 14, 2, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 05:39:38'),
(15, 15, 2, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 05:39:44'),
(16, 16, 2, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 05:39:48'),
(17, 17, 2, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 05:39:53'),
(18, 19, 2, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:46:19'),
(19, 20, 2, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 04:43:15'),
(20, 21, 2, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 05:44:12'),
(21, 22, 2, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 05:44:16'),
(22, 23, 2, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 05:44:18'),
(23, 24, 2, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 05:44:22'),
(24, 25, 2, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 05:44:30'),
(25, 26, 2, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 04:44:09'),
(26, 28, 2, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:47:48'),
(27, 29, 2, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:48:00'),
(28, 30, 2, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 05:44:54'),
(29, 31, 2, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 05:44:58'),
(30, 32, 2, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 05:45:13'),
(31, 33, 2, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 05:45:16'),
(32, 34, 2, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 05:45:21'),
(33, 35, 2, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 05:45:26'),
(34, 37, 2, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:48:49'),
(35, 38, 2, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:48:37'),
(36, 39, 2, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:48:16'),
(37, 40, 2, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 05:46:19'),
(38, 41, 2, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 05:46:22'),
(39, 42, 2, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 05:46:27'),
(40, 43, 2, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 05:46:30'),
(41, 44, 2, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 05:46:35'),
(42, 1, 1, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:45:41'),
(43, 2, 1, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:45:41'),
(44, 14, 1, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:45:41'),
(45, 5, 1, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:45:41'),
(46, 6, 1, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:45:41'),
(47, 7, 1, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:45:41'),
(48, 8, 1, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 04:45:41'),
(49, 3, 1, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 06:28:56'),
(50, 9, 1, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 06:29:05'),
(51, 24, 1, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 06:29:14'),
(52, 12, 1, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 06:29:48'),
(53, 15, 1, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 06:40:32'),
(54, 21, 1, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 06:41:56'),
(55, 17, 1, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 06:30:31'),
(56, 10, 1, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:30:47'),
(57, 20, 1, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:30:57'),
(58, 16, 1, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:31:08'),
(59, 119, 1, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:27:15'),
(60, 35, 1, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:31:32'),
(61, 31, 1, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:31:41'),
(62, 26, 1, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:31:51'),
(63, 25, 1, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:31:59'),
(64, 23, 1, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:32:06'),
(65, 44, 1, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:32:28'),
(66, 32, 1, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:32:38'),
(67, 33, 1, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:32:47'),
(68, 34, 1, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:32:54'),
(69, 130, 1, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:43:32'),
(70, 22, 1, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:36:06'),
(71, 19, 1, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:36:14'),
(72, 29, 1, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:36:31'),
(73, 30, 1, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:36:44'),
(74, 38, 1, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:34:40'),
(75, 137, 1, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:24:55'),
(76, 28, 1, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:37:01'),
(77, 18, 2, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 06:35:08'),
(78, 27, 2, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:47:20'),
(79, 36, 2, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:38:09'),
(80, 45, 2, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 05:48:32'),
(81, 13, 1, 1, NULL, '2025-08-25 19:22:28', '2025-08-26 06:40:07'),
(82, 18, 1, 2, NULL, '2025-08-25 19:22:28', '2025-08-26 06:41:15'),
(83, 27, 1, 3, NULL, '2025-08-25 19:22:28', '2025-08-26 06:42:43'),
(84, 36, 1, 4, NULL, '2025-08-25 19:22:28', '2025-08-26 06:38:13'),
(85, 45, 1, 5, NULL, '2025-08-25 19:22:28', '2025-08-26 06:24:18'),
(132, 5, 3, 1, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(133, 3, 3, 1, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(134, 11, 3, 1, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(135, 4, 3, 1, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(136, 7, 3, 1, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(137, 8, 3, 1, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(138, 10, 3, 1, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(139, 2, 3, 1, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(140, 1, 3, 1, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(141, 20, 3, 2, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(142, 15, 3, 2, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(143, 12, 3, 2, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(144, 14, 3, 2, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(145, 22, 3, 2, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(146, 6, 3, 2, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(147, 9, 3, 2, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(148, 19, 3, 2, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(149, 13, 3, 2, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(150, 21, 3, 3, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(151, 16, 3, 3, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(152, 23, 3, 3, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(153, 18, 3, 3, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(154, 28, 3, 3, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(155, 29, 3, 3, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(156, 26, 3, 3, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(157, 24, 3, 3, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(158, 17, 3, 3, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(159, 30, 3, 4, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(160, 40, 3, 4, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(161, 27, 3, 4, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(162, 33, 3, 4, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(163, 32, 3, 4, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(164, 31, 3, 4, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(165, 25, 3, 4, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(166, 38, 3, 4, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(167, 37, 3, 4, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(168, 36, 3, 5, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(169, 41, 3, 5, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(170, 44, 3, 5, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(171, 39, 3, 5, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(172, 34, 3, 5, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(173, 42, 3, 5, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(174, 43, 3, 5, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(175, 45, 3, 5, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(176, 35, 3, 5, NULL, '2025-08-26 07:05:02', '2025-08-26 07:05:02'),
(177, 46, 3, 1, NULL, '0000-00-00 00:00:00', '2025-09-02 09:21:47'),
(179, 48, 3, 5, NULL, '0000-00-00 00:00:00', '2025-09-02 09:21:47'),
(180, 49, 3, 4, NULL, '0000-00-00 00:00:00', '2025-09-02 09:21:47'),
(181, 50, 3, 3, NULL, '0000-00-00 00:00:00', '2025-09-02 09:21:47'),
(183, 52, 3, 2, NULL, '0000-00-00 00:00:00', '2025-09-02 09:21:47'),
(347, 36, 4, 6, NULL, '2026-01-15 08:33:02', '2026-01-15 08:33:02'),
(348, 21, 4, 3, NULL, '2026-01-15 08:33:13', '2026-01-15 08:33:13'),
(385, 30, 4, 5, NULL, '2026-01-15 08:39:38', '2026-01-15 08:39:38'),
(388, 20, 4, 3, NULL, '2026-01-15 08:39:45', '2026-01-15 08:39:45'),
(390, 41, 4, 5, NULL, '2026-01-15 08:39:45', '2026-01-15 08:39:45'),
(392, 16, 4, 2, NULL, '2026-01-15 08:40:01', '2026-01-15 08:40:01'),
(395, 44, 4, 4, NULL, '2026-01-15 08:40:01', '2026-01-15 08:40:01'),
(399, 15, 4, 1, NULL, '2026-01-15 08:40:13', '2026-01-15 08:40:13'),
(400, 3, 4, 1, NULL, '2026-01-15 08:40:13', '2026-01-15 08:40:13'),
(401, 11, 4, 1, NULL, '2026-01-15 08:40:13', '2026-01-15 08:40:13'),
(403, 12, 4, 2, NULL, '2026-01-15 08:40:13', '2026-01-15 08:40:13'),
(429, 23, 4, 2, NULL, '2026-01-15 08:40:39', '2026-01-15 08:40:39'),
(445, 39, 4, 5, NULL, '2026-01-15 08:40:43', '2026-01-28 17:37:22'),
(491, 143, 4, 5, NULL, '2026-01-15 08:50:46', '2026-01-15 08:50:46'),
(496, 4, 4, 1, NULL, '2026-01-15 08:51:25', '2026-01-15 08:51:25'),
(497, 46, 4, 1, NULL, '2026-01-15 08:51:25', '2026-01-15 08:51:25'),
(503, 52, 4, 3, NULL, '2026-01-15 08:51:25', '2026-01-15 08:51:25'),
(506, 34, 4, 4, NULL, '2026-01-15 08:51:25', '2026-01-15 08:51:25'),
(507, 138, 4, 4, NULL, '2026-01-15 08:51:25', '2026-01-15 08:51:25'),
(511, 144, 4, 5, NULL, '2026-01-15 08:51:25', '2026-01-15 08:51:25'),
(513, 149, 4, 6, NULL, '2026-01-15 08:51:25', '2026-01-15 08:51:25'),
(519, 22, 4, 1, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(520, 8, 4, 1, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(521, 2, 4, 1, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(522, 19, 4, 1, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(523, 1, 4, 1, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(527, 28, 4, 2, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(528, 6, 4, 2, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(529, 9, 4, 2, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(530, 40, 4, 2, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(531, 26, 4, 2, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(532, 13, 4, 2, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(533, 24, 4, 2, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(537, 18, 4, 3, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(538, 14, 4, 3, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(539, 29, 4, 3, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(540, 49, 4, 3, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(541, 50, 4, 3, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(542, 17, 4, 3, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(543, 37, 4, 3, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(548, 27, 4, 4, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(549, 25, 4, 4, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(550, 141, 4, 4, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(551, 139, 4, 4, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(552, 140, 4, 4, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(553, 35, 4, 4, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(558, 142, 4, 5, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(559, 145, 4, 5, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(560, 42, 4, 5, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(561, 31, 4, 5, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(563, 38, 4, 5, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(566, 148, 4, 6, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(567, 48, 4, 6, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(568, 150, 4, 6, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(569, 151, 4, 6, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(570, 32, 4, 6, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(571, 146, 4, 6, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(573, 147, 4, 6, NULL, '2026-01-15 08:53:24', '2026-01-15 08:53:24'),
(634, 152, 4, 6, NULL, '2026-01-15 12:39:02', '2026-01-15 12:39:02'),
(675, 153, 4, 4, NULL, '2026-01-17 20:14:15', '2026-01-17 20:14:15');

-- --------------------------------------------------------

--
-- Struktura tabulky `ligy`
--

CREATE TABLE IF NOT EXISTS `ligy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(100) DEFAULT NULL,
  `cislo` int(11) NOT NULL,
  `poradi` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cislo` (`cislo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=36 ;

--
-- Vypisuji data pro tabulku `ligy`
--

INSERT INTO `ligy` (`id`, `nazev`, `cislo`, `poradi`) VALUES
(1, 'I. Liga FPNET.CZ', 1, 1),
(2, 'II. Liga PODZIMEK', 2, 2),
(3, 'III. Liga AUTO-MOTO-KUBA', 3, 3),
(4, 'IV. Liga SYPSTAV', 4, 4),
(5, 'V. Liga RESTAURACE U KAPRA', 5, 5),
(6, '0. liga', 6, 6);

-- --------------------------------------------------------

--
-- Struktura tabulky `ligy_loga`
--

CREATE TABLE IF NOT EXISTS `ligy_loga` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rocnik_id` int(11) NOT NULL,
  `liga_id` int(11) NOT NULL,
  `logo` varchar(100) NOT NULL,
  `alt` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_logo` (`rocnik_id`,`liga_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=22 ;

--
-- Vypisuji data pro tabulku `ligy_loga`
--

INSERT INTO `ligy_loga` (`id`, `rocnik_id`, `liga_id`, `logo`, `alt`) VALUES
(1, 4, 3, 'sypstav.png', 'SYPSTAV'),
(2, 4, 1, 'fpnet.png', 'FPNET'),
(3, 4, 2, 'podzimek.jpg', 'PODZIMEK'),
(4, 4, 4, 'u-kapra.png', 'Restaurace u Kapra'),
(5, 4, 5, 'automoto.png', 'AUTO MOTO KUBA'),
(6, 3, 1, 'fpnet.png', 'FPNET'),
(7, 3, 2, 'podzimek.jpg', 'PODZIMEK'),
(8, 3, 3, 'automoto.png', 'AUTO MOTO KUBA'),
(9, 3, 4, 'sypstav.png', 'SYPSTAV'),
(10, 3, 5, 'u-kapra.png', 'Restaurace u Kapra'),
(11, 2, 1, 'fpnet.png', 'FPNET'),
(12, 2, 2, 'podzimek.jpg', 'PODZIMEK'),
(13, 2, 3, 'automoto.png', 'AUTO MOTO KUBA'),
(14, 2, 4, 'sypstav.png', 'SYPSTAV'),
(15, 2, 5, 'u-kapra.png', 'Restaurace u Kapra'),
(16, 1, 1, 'fpnet.png', 'FPNET'),
(17, 1, 2, 'podzimek.jpg', 'PODZIMEK'),
(18, 1, 3, 'automoto.png', 'AUTO MOTO KUBA'),
(19, 1, 4, 'sypstav.png', 'SYPSTAV'),
(20, 1, 5, 'u-kapra.png', 'Restaurace u Kapra'),
(21, 4, 6, 'svoboda_holoubek.jpg', 'Svoboda/Holoubek');

-- --------------------------------------------------------

--
-- Struktura tabulky `ligy_nazvy`
--

CREATE TABLE IF NOT EXISTS `ligy_nazvy` (
  `rocnik_id` int(11) NOT NULL,
  `liga_id` int(11) NOT NULL,
  `nazev` varchar(255) NOT NULL,
  PRIMARY KEY (`rocnik_id`,`liga_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

--
-- Vypisuji data pro tabulku `ligy_nazvy`
--

INSERT INTO `ligy_nazvy` (`rocnik_id`, `liga_id`, `nazev`) VALUES
(4, 1, 'FP NET 1. liga'),
(4, 2, 'Podzimek a Synové 2. liga sk. A'),
(4, 3, 'SYPSTAV 2. liga sk. B'),
(4, 4, 'Restaurace U Kapra 3. liga sk. A'),
(4, 5, 'AUTO-MOTO-KUBA 3. liga sk. B'),
(4, 6, 'Holoubek a Svoboda 1. liga ŽENY');

-- --------------------------------------------------------

--
-- Struktura tabulky `n_ligy`
--

CREATE TABLE IF NOT EXISTS `n_ligy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rocnik_id` int(11) NOT NULL,
  `kod` varchar(10) NOT NULL,
  `nazev` varchar(120) NOT NULL,
  `poradi` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rocnik_kod` (`rocnik_id`,`kod`),
  KEY `idx_rocnik_poradi` (`rocnik_id`,`poradi`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=7 ;

--
-- Vypisuji data pro tabulku `n_ligy`
--

INSERT INTO `n_ligy` (`id`, `rocnik_id`, `kod`, `nazev`, `poradi`) VALUES
(1, 4, 'L1', 'FP NET 1. liga', 10),
(2, 4, 'L2A', 'Podzimek a Synové 2. liga sk. A', 20),
(3, 4, 'L2B', 'SYPSTAV 2. liga sk. B', 21),
(4, 4, 'L3A', 'Restaurace U Kapra 3. liga sk. A', 30),
(5, 4, 'L3B', 'AUTO-MOTO-KUBA 3. liga sk. B', 31),
(6, 4, 'L1L', 'Holoubek a Svoboda 1. liga ŽENY', 40);

-- --------------------------------------------------------

--
-- Struktura tabulky `prezidentsky_turnaj`
--

CREATE TABLE IF NOT EXISTS `prezidentsky_turnaj` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rocnik_id` int(11) NOT NULL,
  `nazev` varchar(100) NOT NULL,
  `legs_to_win` tinyint(4) NOT NULL DEFAULT 5,
  `in_out` varchar(20) NOT NULL DEFAULT '201 IN/OUT',
  `status` enum('draft','running','finished') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_turnaj_rocnik` (`rocnik_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=6 ;

--
-- Vypisuji data pro tabulku `prezidentsky_turnaj`
--

INSERT INTO `prezidentsky_turnaj` (`id`, `rocnik_id`, `nazev`, `legs_to_win`, `in_out`, `status`, `created_at`) VALUES
(5, 3, 'Prezidentský pohár Podzim 2025', 5, '201 IN/OUT', 'running', '2025-09-08 17:08:25');

-- --------------------------------------------------------

--
-- Struktura tabulky `prezidentsky_zapas`
--

CREATE TABLE IF NOT EXISTS `prezidentsky_zapas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turnaj_id` int(11) NOT NULL,
  `stage` enum('P','R','O','OF','QF','SF','F') NOT NULL,
  `slot` tinyint(4) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `hrac1_id` int(11) DEFAULT NULL,
  `hrac2_id` int(11) DEFAULT NULL,
  `hrac1_jmeno` varchar(80) DEFAULT NULL,
  `hrac2_jmeno` varchar(80) DEFAULT NULL,
  `skore1` tinyint(4) DEFAULT NULL,
  `skore2` tinyint(4) DEFAULT NULL,
  `vitez` tinyint(4) DEFAULT NULL,
  `next_match_id` int(11) DEFAULT NULL,
  `next_code` varchar(10) DEFAULT NULL,
  `next_pos` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_turnaj_stage_slot` (`turnaj_id`,`stage`,`slot`),
  UNIQUE KEY `uq_turnaj_code` (`turnaj_id`,`code`),
  KEY `idx_next` (`next_match_id`),
  KEY `fk_pp_h1` (`hrac1_id`),
  KEY `fk_pp_h2` (`hrac2_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=132 ;

--
-- Vypisuji data pro tabulku `prezidentsky_zapas`
--

INSERT INTO `prezidentsky_zapas` (`id`, `turnaj_id`, `stage`, `slot`, `code`, `hrac1_id`, `hrac2_id`, `hrac1_jmeno`, `hrac2_jmeno`, `skore1`, `skore2`, `vitez`, `next_match_id`, `next_code`, `next_pos`) VALUES
(40, 5, 'P', 1, 'P1', 49, 44, 'Miroslav Fencík', 'Filip Matějičný', 5, 1, 1, 51, 'R10', 2),
(41, 5, 'P', 2, 'P2', 48, 43, 'Kateřina Fencíková', 'Monika Žiačíková', 2, 5, 2, 56, 'R15', 2),
(42, 5, 'R', 3, 'R1', 14, 39, 'Lukáš Chromý', 'Jan Klíma', 4, 5, 2, 65, 'O8', 2),
(43, 5, 'R', 4, 'R2', 46, 52, 'Jaromír Drda', 'Jan Popelář', 5, 2, 1, 67, 'O10', 2),
(44, 5, 'R', 5, 'R3', 23, 34, 'Jan Choboda', 'Jan Vrátil', 2, 5, 2, 61, 'O4', 2),
(45, 5, 'R', 6, 'R4', 21, 32, 'Adam Krchňavý', 'Míša Beranová', 5, 1, 1, 69, 'O12', 2),
(46, 5, 'R', 7, 'R5', 18, 45, 'Libor Penička', 'Pepa Herkner', 5, 0, 1, 63, 'O6', 2),
(47, 5, 'R', 8, 'R6', 41, 42, 'David Roháček', 'Michal Mlynarček', 5, 2, 1, 71, 'O14', 2),
(48, 5, 'R', 9, 'R7', 26, 24, 'Pavel Kubala', 'Roman Caha', 5, 3, 1, 59, 'O2', 2),
(49, 5, 'R', 10, 'R8', 30, 27, 'David Bártů', 'Jiří Zlatuška', 2, 5, 2, 72, 'O15', 2),
(50, 5, 'R', 11, 'R9', 28, 36, 'Luboš Štěpnička', 'Adéla Krizsanová', 5, 1, 1, 73, 'O16', 2),
(51, 5, 'R', 12, 'R10', 50, 49, 'Ondra Foitl', 'Miroslav Fencík', 0, 5, 2, 58, 'O1', 2),
(52, 5, 'R', 13, 'R11', 20, 35, 'Dominik Beran', 'Vojta Novotný', 5, 4, 1, 70, 'O13', 2),
(53, 5, 'R', 14, 'R12', 12, 25, 'František Soukup', 'Petr Císař', 3, 5, 2, 62, 'O5', 2),
(54, 5, 'R', 15, 'R13', 16, 31, 'Filip Kirchner', 'Pavel Čapoun', 5, 2, 1, 68, 'O11', 2),
(55, 5, 'R', 16, 'R14', 38, 40, 'Tomáš Hlavatý', 'Míra Svoboda', 3, 5, 2, 60, 'O3', 2),
(56, 5, 'R', 17, 'R15', 37, 43, 'Zdeněk Machala', 'Monika Žiačíková', 5, 2, 1, 66, 'O9', 2),
(57, 5, 'R', 18, 'R16', 17, 33, 'Tonda Sobotka', 'Michal Rosa', 5, 2, 1, 64, 'O7', 2),
(58, 5, 'O', 1, 'O1', 1, 49, 'Standa Holoubek', 'Miroslav Fencík', 5, 1, 1, 86, 'OF5', 1),
(59, 5, 'O', 2, 'O2', 3, 26, 'Jakub Šebesta', 'Pavel Kubala', 5, 4, 1, 85, 'OF4', 2),
(60, 5, 'O', 3, 'O3', 4, 40, 'Jan Novotný', 'Míra Svoboda', 0, 5, 2, 82, 'OF1', 1),
(61, 5, 'O', 4, 'O4', 2, 34, 'Pavel Svoboda', 'Jan Vrátil', 5, 1, 1, 89, 'OF8', 2),
(62, 5, 'O', 5, 'O5', 5, 25, 'Filip Vesecký', 'Petr Císař', 5, 4, 1, 83, 'OF2', 2),
(63, 5, 'O', 6, 'O6', 22, 18, 'Martin Bednář', 'Libor Penička', 5, 2, 1, 88, 'OF7', 1),
(64, 5, 'O', 7, 'O7', 8, 17, 'Michal Šimek', 'Tonda Sobotka', 5, 3, 1, 87, 'OF6', 2),
(65, 5, 'O', 8, 'O8', 11, 39, 'Jan Brussmann', 'Jan Klíma', 5, 4, 1, 84, 'OF3', 1),
(66, 5, 'O', 9, 'O9', 6, 37, 'Michal Tausch', 'Zdeněk Machala', 5, 1, 1, 84, 'OF3', 2),
(67, 5, 'O', 10, 'O10', 19, 46, 'Petr Foitl', 'Jaromír Drda', 3, 5, 2, 87, 'OF6', 1),
(68, 5, 'O', 11, 'O11', 7, 16, 'Ladislav Popelář', 'Filip Kirchner', 2, 5, 2, 88, 'OF7', 2),
(69, 5, 'O', 12, 'O12', 10, 21, 'Miroslav Moravský', 'Adam Krchňavý', 5, 1, 1, 83, 'OF2', 1),
(70, 5, 'O', 13, 'O13', 29, 20, 'Lukáš Karkula', 'Dominik Beran', 4, 5, 2, 89, 'OF8', 1),
(71, 5, 'O', 14, 'O14', 9, 41, 'Míra Kratochvíl', 'David Roháček', 5, 2, 1, 82, 'OF1', 2),
(72, 5, 'O', 15, 'O15', 13, 27, 'Radek Ťápal', 'Jiří Zlatuška', 5, 0, 1, 85, 'OF4', 1),
(73, 5, 'O', 16, 'O16', 15, 28, 'František Poledna', 'Luboš Štěpnička', 5, 2, 1, 86, 'OF5', 2),
(82, 5, 'OF', 1, 'OF1', 40, 9, 'Míra Svoboda', 'Míra Kratochvíl', 5, 1, 1, 98, 'QF1', 1),
(83, 5, 'OF', 2, 'OF2', 10, 5, 'Miroslav Moravský', 'Filip Vesecký', 0, 5, 2, 98, 'QF1', 2),
(84, 5, 'OF', 3, 'OF3', 11, 6, 'Jan Brussmann', 'Michal Tausch', 5, 0, 1, 99, 'QF2', 1),
(85, 5, 'OF', 4, 'OF4', 13, 3, 'Radek Ťápal', 'Jakub Šebesta', 3, 5, 2, 99, 'QF2', 2),
(86, 5, 'OF', 5, 'OF5', 1, 15, 'Standa Holoubek', 'František Poledna', 5, 2, 1, 100, 'QF3', 1),
(87, 5, 'OF', 6, 'OF6', 46, 8, 'Jaromír Drda', 'Michal Šimek', 0, 5, 2, 100, 'QF3', 2),
(88, 5, 'OF', 7, 'OF7', 22, 16, 'Martin Bednář', 'Filip Kirchner', 5, 4, 1, 101, 'QF4', 1),
(89, 5, 'OF', 8, 'OF8', 20, 2, 'Dominik Beran', 'Pavel Svoboda', 1, 5, 2, 101, 'QF4', 2),
(98, 5, 'QF', 1, 'QF1', 40, 5, 'Míra Svoboda', 'Filip Vesecký', 1, 5, 2, 114, 'SF1', 1),
(99, 5, 'QF', 2, 'QF2', 11, 3, 'Jan Brussmann', 'Jakub Šebesta', 5, 3, 1, 114, 'SF1', 2),
(100, 5, 'QF', 3, 'QF3', 1, 8, 'Standa Holoubek', 'Michal Šimek', 5, 0, 1, 115, 'SF2', 1),
(101, 5, 'QF', 4, 'QF4', 22, 2, 'Martin Bednář', 'Pavel Svoboda', 5, 3, 1, 115, 'SF2', 2),
(114, 5, 'SF', 1, 'SF1', 5, 11, 'Filip Vesecký', 'Jan Brussmann', 4, 5, 2, 116, 'F1', 1),
(115, 5, 'SF', 2, 'SF2', 1, 22, 'Standa Holoubek', 'Martin Bednář', 5, 0, 1, 116, 'F1', 2),
(116, 5, 'F', 1, 'F1', 11, 1, 'Jan Brussmann', 'Standa Holoubek', 5, 3, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktura tabulky `rezervace`
--

CREATE TABLE IF NOT EXISTS `rezervace` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datum` date NOT NULL,
  `hodina` tinyint(4) NOT NULL,
  `terc` tinyint(4) NOT NULL,
  `jmeno` varchar(60) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_slot` (`datum`,`hodina`,`terc`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=21 ;

--
-- Vypisuji data pro tabulku `rezervace`
--

INSERT INTO `rezervace` (`id`, `datum`, `hodina`, `terc`, `jmeno`, `created_at`) VALUES
(4, '2026-02-05', 17, 1, 'Soukup', '2026-02-02 08:54:38'),
(6, '2026-02-11', 17, 4, 'Matoušek', '2026-02-02 11:21:28'),
(7, '2026-02-05', 16, 3, 'Matějíčný', '2026-02-04 10:17:31'),
(8, '2026-02-05', 17, 3, 'Matějíčný', '2026-02-04 10:17:40'),
(9, '2026-02-05', 18, 3, 'Matějíčný', '2026-02-04 11:01:13'),
(11, '2026-02-05', 16, 6, 'Karkula', '2026-02-04 17:59:21'),
(12, '2026-02-05', 17, 6, 'Karkula', '2026-02-04 17:59:34'),
(13, '2026-02-07', 18, 6, 'Pěničkova', '2026-02-04 18:32:06'),
(14, '2026-02-07', 20, 6, 'Pěničkova', '2026-02-04 18:33:12'),
(18, '2026-02-12', 17, 4, 'Matoušek', '2026-02-05 08:38:21'),
(19, '2026-02-05', 18, 2, 'Soukup', '2026-02-05 13:07:32'),
(20, '2026-02-13', 16, 1, 'Choboda', '2026-02-05 18:36:07');

-- --------------------------------------------------------

--
-- Struktura tabulky `rezervace_old`
--

CREATE TABLE IF NOT EXISTS `rezervace_old` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `terc_id` int(11) NOT NULL,
  `jmeno` varchar(100) NOT NULL,
  `datum` date NOT NULL,
  `cas` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_slot` (`terc_id`,`datum`,`cas`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci ROW_FORMAT=COMPACT AUTO_INCREMENT=250 ;

--
-- Vypisuji data pro tabulku `rezervace_old`
--

INSERT INTO `rezervace_old` (`id`, `terc_id`, `jmeno`, `datum`, `cas`, `created_at`) VALUES
(30, 2, 'beran', '2025-06-24', '19:00:00', '2025-06-25 08:04:10'),
(32, 1, 'beran', '2025-06-24', '21:00:00', '2025-06-25 08:22:37'),
(39, 1, 'Beran', '2025-06-27', '17:00:00', '2025-06-26 18:04:56'),
(40, 1, 'beran', '2025-06-28', '19:00:00', '2025-06-27 14:17:56'),
(44, 1, 'Beran', '2025-08-20', '17:00:00', '2025-08-22 11:28:35'),
(53, 1, 'Beran', '2025-09-05', '18:00:00', '2025-09-03 06:36:12'),
(56, 1, 'Roháček', '2025-09-30', '16:00:00', '2025-09-29 13:30:59'),
(57, 4, 'Beran', '2025-09-30', '18:00:00', '2025-09-29 13:45:26'),
(58, 1, 'Žiačiková', '2025-09-30', '18:00:00', '2025-09-29 15:53:16'),
(59, 1, 'Klíma', '2025-09-30', '19:00:00', '2025-09-29 15:55:12'),
(60, 1, 'Ťápal', '2025-10-01', '15:00:00', '2025-09-30 11:23:30'),
(61, 1, 'Ťápal', '2025-10-01', '16:00:00', '2025-09-30 11:23:58'),
(62, 2, 'Soukup', '2025-09-30', '17:00:00', '2025-09-30 14:01:50'),
(63, 4, 'Beran', '2025-10-08', '17:00:00', '2025-10-06 07:40:53'),
(65, 2, 'Šebesta', '2025-10-10', '17:00:00', '2025-10-06 07:41:18'),
(66, 3, 'Šebesta', '2025-10-10', '17:00:00', '2025-10-06 07:41:29'),
(67, 3, 'Šebesta', '2025-10-10', '16:00:00', '2025-10-06 07:41:45'),
(68, 2, 'Šebesta', '2025-10-10', '18:00:00', '2025-10-06 07:42:00'),
(69, 3, 'Šebesta', '2025-10-10', '18:00:00', '2025-10-06 07:42:16'),
(70, 1, 'Roháček', '2025-10-11', '18:00:00', '2025-10-06 10:04:49'),
(71, 1, 'Adél', '2025-10-09', '18:00:00', '2025-10-06 17:36:07'),
(72, 3, 'Penicka', '2025-10-08', '18:00:00', '2025-10-06 18:27:05'),
(73, 1, 'Karkula', '2025-10-08', '18:00:00', '2025-10-06 18:40:45'),
(74, 2, 'Žiačiková', '2025-10-08', '17:00:00', '2025-10-06 18:42:56'),
(75, 2, 'Šebesta', '2025-10-10', '16:00:00', '2025-10-06 18:54:01'),
(76, 1, 'Roháček', '2025-10-11', '19:00:00', '2025-10-06 19:23:09'),
(77, 4, 'Pěnička', '2025-10-07', '18:00:00', '2025-10-07 10:57:53'),
(79, 1, 'Soukup', '2025-10-08', '17:00:00', '2025-10-07 15:59:59'),
(80, 2, 'Žiačiková', '2025-10-10', '14:00:00', '2025-10-07 20:08:55'),
(81, 2, 'Matějíčný - Fencík', '2025-10-09', '17:00:00', '2025-10-08 07:05:35'),
(82, 3, 'Kirchy', '2025-10-08', '17:00:00', '2025-10-08 09:53:50'),
(83, 1, 'Drda Jaromír-Popelář Jan', '2025-10-11', '14:00:00', '2025-10-09 20:02:25'),
(84, 4, 'Jan Popi Popelář', '2025-10-11', '14:00:00', '2025-10-09 21:04:16'),
(86, 4, 'Jan Popi Popelář', '2025-10-11', '15:00:00', '2025-10-09 21:06:17'),
(87, 1, 'Štěpnička', '2025-10-14', '18:00:00', '2025-10-11 14:54:12'),
(88, 1, 'Novotný', '2025-10-15', '17:00:00', '2025-10-12 17:51:14'),
(89, 2, 'Drda', '2025-10-15', '18:00:00', '2025-10-12 18:08:20'),
(90, 1, 'Machala', '2025-10-14', '16:00:00', '2025-10-12 19:58:05'),
(91, 4, 'Karkula', '2025-10-14', '18:00:00', '2025-10-13 06:59:26'),
(97, 4, 'popi', '2025-10-15', '17:00:00', '2025-10-14 05:25:53'),
(98, 2, 'Soukup', '2025-10-14', '17:00:00', '2025-10-14 07:00:28'),
(99, 1, 'Klíma', '2025-10-16', '18:00:00', '2025-10-14 08:33:24'),
(100, 3, 'Fencík', '2025-10-16', '18:00:00', '2025-10-14 09:41:40'),
(101, 3, 'Fencík', '2025-10-16', '19:00:00', '2025-10-14 10:11:02'),
(102, 4, 'popi', '2025-10-15', '19:00:00', '2025-10-15 05:09:49'),
(104, 4, 'Penda', '2025-10-17', '17:00:00', '2025-10-17 03:58:03'),
(105, 1, 'Novotný', '2025-10-21', '17:00:00', '2025-10-18 14:43:57'),
(106, 3, 'Šebesta Brussmann', '2025-10-23', '19:00:00', '2025-10-19 18:16:52'),
(107, 1, 'kratochvil', '2025-10-22', '17:00:00', '2025-10-20 04:58:23'),
(108, 1, 'kratochvil', '2025-10-22', '18:00:00', '2025-10-20 04:58:51'),
(110, 2, 'Poledna', '2025-10-22', '17:00:00', '2025-10-20 08:13:17'),
(111, 1, 'Adéla', '2025-10-24', '20:00:00', '2025-10-20 09:49:59'),
(112, 2, 'Kratochvíl', '2025-10-24', '17:00:00', '2025-10-21 06:06:19'),
(113, 2, 'Kratochvíl', '2025-10-24', '18:00:00', '2025-10-21 19:52:45'),
(114, 4, 'Hlavatý', '2025-10-24', '17:00:00', '2025-10-23 16:43:40'),
(115, 2, 'Soukup', '2025-10-24', '16:00:00', '2025-10-24 09:54:20'),
(116, 3, 'Fencík', '2025-10-28', '18:00:00', '2025-10-25 14:27:44'),
(117, 1, 'Novotný V', '2025-10-30', '17:00:00', '2025-10-27 17:11:36'),
(118, 1, 'Soukup', '2025-10-28', '18:00:00', '2025-10-28 15:35:07'),
(119, 1, 'Novotný', '2025-10-30', '18:00:00', '2025-10-28 16:44:53'),
(120, 2, 'Kratochvíl', '2025-10-29', '18:00:00', '2025-10-29 07:52:43'),
(121, 2, 'Kratochvíl', '2025-10-29', '19:00:00', '2025-10-29 07:53:34'),
(122, 1, 'Popelář', '2025-10-29', '19:00:00', '2025-10-29 08:06:26'),
(123, 4, 'Bártů', '2025-10-30', '18:00:00', '2025-10-29 09:48:47'),
(124, 4, 'Bártů', '2025-10-30', '19:00:00', '2025-10-29 09:49:07'),
(125, 3, 'Šebesta', '2025-10-29', '18:00:00', '2025-10-29 12:55:35'),
(126, 3, 'Šebesta', '2025-10-29', '19:00:00', '2025-10-29 12:55:49'),
(127, 4, 'Šebesta', '2025-10-29', '18:00:00', '2025-10-29 12:56:03'),
(128, 4, 'Šebesta', '2025-10-29', '19:00:00', '2025-10-29 12:56:25'),
(129, 4, 'Beran', '2025-10-31', '19:00:00', '2025-10-30 07:23:35'),
(130, 1, 'Klíma', '2025-10-31', '18:00:00', '2025-10-30 17:00:12'),
(131, 1, 'Soukup', '2025-10-31', '16:00:00', '2025-10-31 09:47:01'),
(132, 1, 'Soukup', '2025-10-31', '17:00:00', '2025-10-31 09:47:35'),
(133, 3, 'Zlatuška', '2025-10-31', '17:00:00', '2025-10-31 11:53:41'),
(134, 2, 'Roman Caha', '2025-10-31', '19:00:00', '2025-10-31 12:57:38'),
(135, 3, 'Fencík', '2025-11-05', '17:00:00', '2025-11-02 14:21:57'),
(136, 3, 'Fencík', '2025-11-05', '18:00:00', '2025-11-02 14:23:32'),
(137, 2, 'Fencíková', '2025-11-05', '17:00:00', '2025-11-02 15:00:02'),
(138, 2, 'Fencíková', '2025-11-05', '18:00:00', '2025-11-02 16:26:46'),
(139, 1, 'Roháček', '2025-11-05', '18:00:00', '2025-11-03 07:24:32'),
(140, 4, 'Soukup', '2025-11-05', '18:00:00', '2025-11-03 09:36:36'),
(141, 1, 'Klíma', '2025-11-05', '17:00:00', '2025-11-04 16:17:20'),
(142, 1, 'Roháček', '2025-11-05', '19:00:00', '2025-11-04 17:21:37'),
(143, 1, 'Pepa', '2025-11-06', '18:00:00', '2025-11-06 11:26:49'),
(145, 1, 'Klíma', '2025-11-07', '20:00:00', '2025-11-07 16:58:07'),
(146, 3, 'Novotný', '2025-11-12', '17:00:00', '2025-11-10 12:24:33'),
(147, 3, 'Novotný', '2025-11-12', '18:00:00', '2025-11-10 13:08:30'),
(148, 3, 'Fencík', '2025-11-13', '18:00:00', '2025-11-10 13:35:24'),
(149, 4, 'Karkula', '2025-11-13', '18:00:00', '2025-11-10 13:46:01'),
(150, 3, 'Bártů', '2025-11-13', '19:00:00', '2025-11-10 13:50:18'),
(151, 4, 'Beran', '2025-11-13', '19:00:00', '2025-11-10 14:10:09'),
(152, 2, 'penicka', '2025-11-13', '19:00:00', '2025-11-10 15:38:29'),
(153, 1, 'Šimek', '2025-11-13', '18:00:00', '2025-11-10 15:41:15'),
(154, 2, 'Štěpnička', '2025-11-13', '18:00:00', '2025-11-10 16:39:12'),
(155, 1, 'Štěpnička', '2025-11-13', '19:00:00', '2025-11-10 16:47:50'),
(156, 4, 'Kirchy', '2025-11-13', '17:00:00', '2025-11-10 18:16:47'),
(159, 2, 'Pepa', '2025-11-12', '17:00:00', '2025-11-11 11:20:27'),
(160, 1, 'Klíma', '2025-11-12', '17:00:00', '2025-11-11 11:21:03'),
(161, 3, 'Holoubek', '2025-11-11', '19:00:00', '2025-11-11 14:40:53'),
(162, 1, 'Kratochvíl', '2025-11-14', '18:00:00', '2025-11-12 10:53:15'),
(163, 4, 'choby', '2025-11-12', '17:00:00', '2025-11-12 10:57:59'),
(164, 1, 'Kratochvíl', '2025-11-14', '17:00:00', '2025-11-14 13:29:57'),
(165, 1, 'Klíma', '2025-11-21', '20:00:00', '2025-11-14 21:42:54'),
(166, 1, 'Klíma', '2025-11-19', '18:00:00', '2025-11-15 11:15:46'),
(168, 4, 'TomášH.', '2025-11-18', '17:00:00', '2025-11-18 11:34:24'),
(169, 4, 'Hlavatý', '2025-11-18', '18:00:00', '2025-11-18 11:35:38'),
(170, 1, 'David', '2025-11-18', '16:00:00', '2025-11-18 12:52:12'),
(171, 1, 'David', '2025-11-18', '17:00:00', '2025-11-18 12:58:36'),
(172, 2, 'Vráťa', '2025-11-18', '17:00:00', '2025-11-18 14:38:55'),
(177, 4, 'Karkula', '2025-11-20', '18:00:00', '2025-11-18 17:31:35'),
(178, 2, 'Štěpnička', '2025-11-20', '19:00:00', '2025-11-18 17:32:24'),
(179, 1, 'Adél', '2025-11-20', '18:00:00', '2025-11-19 11:03:27'),
(180, 3, 'Šéfík', '2025-11-19', '18:00:00', '2025-11-19 13:29:45'),
(181, 3, 'Šéfík', '2025-11-19', '17:00:00', '2025-11-19 13:29:52'),
(182, 2, 'choby', '2025-11-20', '18:00:00', '2025-11-19 14:00:41'),
(183, 3, 'Štěpnička', '2025-11-20', '18:00:00', '2025-11-20 12:44:54'),
(184, 1, 'choby', '2025-11-21', '19:00:00', '2025-11-20 17:26:55'),
(185, 2, 'Šebesta', '2025-11-26', '17:00:00', '2025-11-23 17:44:08'),
(186, 2, 'Šebesta', '2025-11-26', '18:00:00', '2025-11-23 17:44:31'),
(187, 2, 'Šebesta', '2025-11-26', '19:00:00', '2025-11-23 17:44:54'),
(188, 4, 'kirchy', '2025-11-26', '17:00:00', '2025-11-24 18:30:59'),
(189, 1, 'Roháček', '2025-11-29', '16:00:00', '2025-11-25 10:50:21'),
(190, 1, 'Roháček', '2025-11-29', '17:00:00', '2025-11-25 10:50:40'),
(191, 1, 'Roháček', '2025-11-29', '18:00:00', '2025-11-25 10:51:00'),
(192, 1, 'Roháček', '2025-11-29', '19:00:00', '2025-11-25 10:51:53'),
(193, 1, 'Roháček', '2025-11-29', '21:00:00', '2025-11-25 10:52:36'),
(194, 1, 'Roháček', '2025-11-29', '20:00:00', '2025-11-25 10:53:13'),
(196, 3, 'Pepa', '2025-11-26', '18:00:00', '2025-11-26 09:26:33'),
(197, 4, 'Bártů', '2025-11-27', '18:00:00', '2025-11-26 11:59:01'),
(198, 1, 'Adél', '2025-11-26', '18:00:00', '2025-11-26 12:35:48'),
(199, 4, 'Karkula', '2025-11-29', '18:00:00', '2025-11-28 21:33:36'),
(201, 1, 'Roháček', '2025-12-02', '17:00:00', '2025-12-01 15:33:15'),
(202, 1, 'Roháček', '2025-12-02', '18:00:00', '2025-12-01 15:33:50'),
(203, 3, 'Fencíková', '2025-12-02', '18:00:00', '2025-12-01 15:58:21'),
(204, 3, 'Fencíková', '2025-12-02', '17:00:00', '2025-12-01 15:58:40'),
(206, 2, 'Šimek', '2025-12-04', '18:00:00', '2025-12-01 19:05:02'),
(207, 4, 'Karkula', '2025-12-04', '16:00:00', '2025-12-02 06:41:38'),
(208, 2, 'Šebesta - Ťápal pohár', '2025-12-09', '18:00:00', '2025-12-02 13:13:30'),
(209, 3, 'Fencík', '2025-12-02', '19:00:00', '2025-12-02 14:33:23'),
(210, 1, 'Soukup', '2025-12-03', '17:00:00', '2025-12-02 16:56:59'),
(211, 3, 'M.B.', '2025-12-05', '19:00:00', '2025-12-05 07:49:46'),
(213, 4, 'Fencík', '2025-12-10', '18:00:00', '2025-12-09 21:20:36'),
(214, 4, 'Bártů', '2025-12-19', '17:00:00', '2025-12-17 09:43:51'),
(215, 4, 'Bártů', '2025-12-19', '19:00:00', '2025-12-17 09:44:18'),
(216, 4, 'Bártů', '2025-12-19', '18:00:00', '2025-12-17 09:44:47'),
(217, 1, 'Roháček', '2025-12-20', '17:00:00', '2025-12-18 07:34:06'),
(218, 1, 'Roháček', '2025-12-20', '18:00:00', '2025-12-18 07:34:34'),
(219, 1, 'Roháček', '2025-12-20', '19:00:00', '2025-12-18 07:34:58'),
(220, 2, 'Matějíčný', '2025-12-20', '17:00:00', '2025-12-18 09:08:05'),
(221, 2, 'Holoubek', '2025-12-19', '17:00:00', '2025-12-18 18:25:22'),
(222, 3, 'Kratochvíl', '2025-12-20', '17:00:00', '2025-12-20 07:43:04'),
(226, 2, 'pohár', '2025-12-30', '16:00:00', '2025-12-28 09:46:13'),
(227, 2, 'pohár', '2025-12-30', '17:00:00', '2025-12-28 09:46:35'),
(228, 2, 'pohár', '2025-12-30', '18:00:00', '2025-12-28 09:47:00'),
(229, 3, 'pohár', '2025-12-30', '16:00:00', '2025-12-28 09:47:28'),
(230, 3, 'pohár', '2025-12-30', '17:00:00', '2025-12-28 09:47:49'),
(231, 3, 'pohár', '2025-12-30', '18:00:00', '2025-12-28 09:48:09'),
(232, 2, 'Šebesta', '2025-12-30', '19:00:00', '2025-12-28 09:49:21'),
(233, 3, 'Šebesta', '2025-12-30', '19:00:00', '2025-12-28 09:49:45'),
(234, 2, 'Holoubek', '2025-12-30', '20:00:00', '2025-12-28 09:50:06'),
(235, 3, 'Svoboda', '2025-12-30', '20:00:00', '2025-12-28 09:50:53'),
(236, 4, 'Fencík', '2025-12-30', '14:00:00', '2025-12-29 16:41:59'),
(237, 1, 'Novotný', '2026-01-02', '14:00:00', '2025-12-29 16:59:29'),
(239, 1, 'Tausch - Kratochvíl', '2025-12-30', '14:00:00', '2025-12-29 19:02:08'),
(240, 1, 'Tausch - Soukup', '2025-12-30', '15:00:00', '2025-12-29 19:02:23'),
(241, 4, 'Karkula', '2025-12-30', '19:00:00', '2025-12-30 11:26:28'),
(242, 3, 'Fencík', '2026-01-03', '15:00:00', '2026-01-02 14:54:59'),
(243, 1, 'Štěpnička', '2026-01-09', '18:00:00', '2026-01-05 14:36:36'),
(244, 2, 'Kratochvíl', '2026-01-09', '17:00:00', '2026-01-07 10:10:37'),
(245, 1, 'Vráťa', '2026-01-09', '17:00:00', '2026-01-08 17:01:40'),
(247, 2, 'Beran', '2026-01-24', '20:00:00', '2026-01-18 08:41:12'),
(248, 1, 'beran', '2026-01-31', '19:00:00', '2026-01-18 08:46:56'),
(249, 1, 'Beran', '2026-01-30', '17:00:00', '2026-01-18 08:47:35');

-- --------------------------------------------------------

--
-- Struktura tabulky `rocniky`
--

CREATE TABLE IF NOT EXISTS `rocniky` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(100) DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=5 ;

--
-- Vypisuji data pro tabulku `rocniky`
--

INSERT INTO `rocniky` (`id`, `nazev`, `locked`) VALUES
(1, 'Podzim 2024', 1),
(2, 'Jaro 2025', 1),
(3, 'Podzim 2025', 1),
(4, 'Jaro 2026', 0);

-- --------------------------------------------------------

--
-- Struktura tabulky `seznam_hracu_web`
--

CREATE TABLE IF NOT EXISTS `seznam_hracu_web` (
  `klubove_cislo` varchar(10) NOT NULL,
  `jmeno` varchar(100) NOT NULL,
  `prezdivka` varchar(100) DEFAULT NULL,
  `bydliste` varchar(100) DEFAULT NULL,
  `vek` tinyint(3) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

--
-- Vypisuji data pro tabulku `seznam_hracu_web`
--

INSERT INTO `seznam_hracu_web` (`klubove_cislo`, `jmeno`, `prezdivka`, `bydliste`, `vek`) VALUES
('001', 'Jakub Šebesta ', 'El Presidento ', 'Třešť ', 35),
('002', 'Stanislav Holoubek ', 'Standa', 'Třešť ', 45),
('003', 'Libor Pěnička ', 'Pěnda', 'Třešť ', 40),
('004', 'Pavel Svoboda ', 'Svobi ', 'Třešť ', 46),
('005', 'Dominik Beran', 'Bery', 'Třešť', 33),
('006', 'Vojtěch Novotný', 'Nechto', 'Třešť', 32),
('007', 'Michal Tausch', 'Michal', 'Třešť', 33),
('008', 'Michaela Beranová ', 'Míša ', 'Třešť ', 29),
('009', 'Jan Brussmann', 'Jan', 'Třešť', 40),
('010', 'Michal Šimek ', 'Jáca', 'Stará Říše ', 42),
('011', 'Jan Novotný ', 'Mazel', 'Panská Lhota ', 41),
('012', 'Jaromír Drda', 'George ', 'Horní Cerekev ', 49),
('013', 'Martin Bednář', 'Hippi', 'Jihlava', 41),
('014', 'František Poledna ', 'Fugas ', 'Čenkov ', 48),
('015', 'Petr Foitl ', 'Pitrs ', 'Lovětín ', 46),
('016', 'Pavel Kubala', 'Brumla', 'Třešť ', 37),
('017', 'František Soukup', 'Fany', 'Lovětín', 41),
('018', 'Miroslav Kratochvíl ', 'Plašan', 'Jihlava ', 46),
('019', 'Radek Ťápal', 'Radek', 'Třešť ', 48),
('020', 'Luboš Štěpnička ', 'Luboš ', 'Nová Říše ', 38),
('021', 'Jan Choboda', 'Choby (Janek)', 'Třešť ', 36),
('022', 'Roman Caha', 'Roman', 'Třešť', 34),
('023', 'Filip Kirchner', 'Kirchy', 'Třešť ', 32),
('024', 'Jaromír Svoboda', 'Míra Svoboda', 'Třešť ', 38),
('025', 'Adam Krchňavý ', 'Krchňa', 'Třešť', 27),
('026', 'Jan Popelář', 'Popi', 'Telč', 47),
('027', 'Lukáš Chromý ', 'Chromák', 'Třešť ', 32),
('028', 'Lukáš Karkula', 'Lukáš', 'Bohuslavice', 35),
('029', 'Antonín Sobotka', 'Tony', 'Třešť', 64),
('030', 'Zdeněk Machala ', 'Majda', 'Třešť ', 42),
('031', 'Miroslav Fencík ', 'Fenca', 'Čenkov ', 51),
('032', 'Stanislav Novák ', 'Standa', 'Panenska Rozsíčka ', 32),
('033', 'Petr Císař', 'Císi', 'Linz', 32),
('034', 'Filip Matějíčný', 'Filip', 'Třešť', 24),
('035', 'Jiří Zlatuška ', 'Zlaťák', 'Jihlava ', 39),
('036', 'Jan Klíma', 'Klímič', 'Třešť', 31),
('038', 'Václav Tetour', 'Vaclav', 'Třešť', 36),
('039', 'Jaroslav Vávrů ', 'Jára', 'Nevcehle ', 29),
('040', 'Petr Krejčí', 'Keidza', 'Nevcehle', 33),
('041', 'Tomáš Fencík', 'Fenca ml.', 'Čenkov ', 23),
('042', 'Michal Babický', 'pan Bába ', 'Jihlava ', 37),
('043', 'David Bártů ', 'David', 'Nová Říše ', 41),
('044', 'Pavel Čapoun ', 'Čapy', 'Markvartice ', 30),
('045', 'David Roháček', 'David', 'Horni Cerekev', 50),
('046', 'Michal Mlynárček ', 'Bolek', 'Třešť ', 40),
('048', 'Tomáš Hlavatý', 'Tomáš', 'Dolní Cerekev', 26),
('049', 'Marek Pecina', 'The Hammer', 'Jihlava ', 27),
('050', 'Jan Křen', 'Křenďa', 'Telč', 38),
('051', 'Jan Matoušek ', 'Maty', 'Třešť ', 30),
('053', 'Adéla Križanová', 'Addie', 'Třešť', 29),
('054', 'Kateřina Fencíková ', 'Katka', 'Čenkov', 46),
('055', 'Míša Pěničkova', 'Míša ', 'Třešť ', 31),
('056', 'Sabina Němcová ', 'Sabča ', 'Třešť ', 29),
('057', 'Jitka Šebestová', 'Jitka', 'Třešť', 38),
('058', 'Jaroslava Knotková', 'Jářa', 'Čenkov', 42),
('059', 'Ludmila Polednová ', 'Lída ', 'Čenkov ', 39),
('060', 'Martina Nováková ', 'Martina ', 'Třešť ', 32),
('037', 'Jan Vrátil', 'Vráťa', 'Třešť', 30);

-- --------------------------------------------------------

--
-- Struktura tabulky `turnaje`
--

CREATE TABLE IF NOT EXISTS `turnaje` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(255) NOT NULL,
  `rocnik_id` int(11) NOT NULL,
  `stav` enum('priprava','probiha','ukonceno') DEFAULT 'priprava',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=8 ;

--
-- Vypisuji data pro tabulku `turnaje`
--

INSERT INTO `turnaje` (`id`, `nazev`, `rocnik_id`, `stav`, `created_at`) VALUES
(7, 'Prezidentský pohár Jaro 2026 Cricket', 4, 'priprava', '2026-01-20 12:42:18');

-- --------------------------------------------------------

--
-- Struktura tabulky `turnaj_hraci`
--

CREATE TABLE IF NOT EXISTS `turnaj_hraci` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turnaj_id` int(11) NOT NULL,
  `hrac_id` int(11) NOT NULL,
  `nasazeni` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `turnaj_id` (`turnaj_id`,`hrac_id`),
  KEY `idx_turnaj_hraci_turnaj` (`turnaj_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=436 ;

--
-- Vypisuji data pro tabulku `turnaj_hraci`
--

INSERT INTO `turnaj_hraci` (`id`, `turnaj_id`, `hrac_id`, `nasazeni`) VALUES
(374, 7, 21, NULL),
(375, 7, 36, NULL),
(376, 7, 30, NULL),
(377, 7, 41, NULL),
(378, 7, 20, NULL),
(379, 7, 152, NULL),
(380, 7, 16, NULL),
(381, 7, 44, NULL),
(382, 7, 15, NULL),
(383, 7, 12, NULL),
(384, 7, 3, NULL),
(385, 7, 11, NULL),
(386, 7, 23, NULL),
(387, 7, 39, NULL),
(388, 7, 143, NULL),
(389, 7, 144, NULL),
(390, 7, 4, NULL),
(391, 7, 52, NULL),
(392, 7, 34, NULL),
(393, 7, 138, NULL),
(394, 7, 46, NULL),
(395, 7, 149, NULL),
(396, 7, 27, NULL),
(397, 7, 148, NULL),
(398, 7, 48, NULL),
(399, 7, 18, NULL),
(400, 7, 150, NULL),
(401, 7, 28, NULL),
(402, 7, 14, NULL),
(403, 7, 29, NULL),
(404, 7, 142, NULL),
(405, 7, 22, NULL),
(406, 7, 151, NULL),
(407, 7, 145, NULL),
(408, 7, 42, NULL),
(409, 7, 8, NULL),
(410, 7, 6, NULL),
(411, 7, 9, NULL),
(412, 7, 40, NULL),
(413, 7, 49, NULL),
(414, 7, 32, NULL),
(415, 7, 146, NULL),
(416, 7, 43, NULL),
(417, 7, 50, NULL),
(418, 7, 31, NULL),
(419, 7, 26, NULL),
(420, 7, 2, NULL),
(421, 7, 45, NULL),
(422, 7, 25, NULL),
(423, 7, 19, NULL),
(424, 7, 141, NULL),
(425, 7, 13, NULL),
(426, 7, 24, NULL),
(427, 7, 147, NULL),
(428, 7, 1, NULL),
(429, 7, 139, NULL),
(430, 7, 153, NULL),
(431, 7, 38, NULL),
(432, 7, 17, NULL),
(433, 7, 140, NULL),
(434, 7, 35, NULL),
(435, 7, 37, NULL);

-- --------------------------------------------------------

--
-- Struktura tabulky `turnaj_zapasy`
--

CREATE TABLE IF NOT EXISTS `turnaj_zapasy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turnaj_id` int(11) NOT NULL,
  `kolo` int(11) NOT NULL,
  `poradi` int(11) NOT NULL,
  `hrac1_id` int(11) DEFAULT NULL,
  `hrac2_id` int(11) DEFAULT NULL,
  `skore1` int(11) DEFAULT NULL,
  `skore2` int(11) DEFAULT NULL,
  `vitez_id` int(11) DEFAULT NULL,
  `next_match_id` int(11) DEFAULT NULL,
  `next_slot` enum('hrac1','hrac2') DEFAULT NULL,
  `navazuje_na_1` int(11) DEFAULT NULL,
  `navazuje_na_2` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_turnaj_zapasy_kolo` (`turnaj_id`,`kolo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=379 ;

--
-- Vypisuji data pro tabulku `turnaj_zapasy`
--

INSERT INTO `turnaj_zapasy` (`id`, `turnaj_id`, `kolo`, `poradi`, `hrac1_id`, `hrac2_id`, `skore1`, `skore2`, `vitez_id`, `next_match_id`, `next_slot`, `navazuje_na_1`, `navazuje_na_2`) VALUES
(316, 7, 1, 1, 2, 139, NULL, NULL, NULL, 348, 'hrac1', NULL, NULL),
(317, 7, 1, 2, 1, 36, NULL, NULL, NULL, 349, 'hrac1', NULL, NULL),
(318, 7, 1, 3, 11, 150, NULL, NULL, NULL, 350, 'hrac1', NULL, NULL),
(319, 7, 1, 4, 46, 0, NULL, NULL, 46, 351, 'hrac1', NULL, NULL),
(320, 7, 1, 5, 3, 32, NULL, NULL, NULL, 352, 'hrac1', NULL, NULL),
(321, 7, 1, 6, 22, 146, NULL, NULL, NULL, 353, 'hrac1', NULL, NULL),
(322, 7, 1, 7, 8, 142, NULL, NULL, NULL, 354, 'hrac1', NULL, NULL),
(323, 7, 1, 8, 6, 25, NULL, NULL, NULL, 355, 'hrac1', NULL, NULL),
(324, 7, 1, 9, 15, 148, NULL, NULL, NULL, 356, 'hrac1', NULL, NULL),
(325, 7, 1, 10, 28, 143, NULL, NULL, NULL, 357, 'hrac1', NULL, NULL),
(326, 7, 1, 11, 52, 145, NULL, NULL, NULL, 358, 'hrac1', NULL, NULL),
(327, 7, 1, 12, 19, 38, NULL, NULL, NULL, 359, 'hrac1', NULL, NULL),
(328, 7, 1, 13, 29, 48, NULL, NULL, NULL, 360, 'hrac1', NULL, NULL),
(329, 7, 1, 14, 14, 35, NULL, NULL, NULL, 361, 'hrac1', NULL, NULL),
(330, 7, 1, 15, 13, 152, NULL, NULL, NULL, 362, 'hrac1', NULL, NULL),
(331, 7, 1, 16, 12, 140, NULL, NULL, NULL, 363, 'hrac1', NULL, NULL),
(332, 7, 1, 17, 9, 27, 3, 0, 9, 363, 'hrac2', NULL, NULL),
(333, 7, 1, 18, 16, 144, NULL, NULL, NULL, 362, 'hrac2', NULL, NULL),
(334, 7, 1, 19, 17, 43, 3, 0, 17, 361, 'hrac2', NULL, NULL),
(335, 7, 1, 20, 20, 4, NULL, NULL, NULL, 360, 'hrac2', NULL, NULL),
(336, 7, 1, 21, 18, 45, 3, 0, 18, 359, 'hrac2', NULL, NULL),
(337, 7, 1, 22, 21, 44, NULL, NULL, NULL, 358, 'hrac2', NULL, NULL),
(338, 7, 1, 23, 23, 141, NULL, NULL, NULL, 357, 'hrac2', NULL, NULL),
(339, 7, 1, 24, 50, 31, NULL, NULL, NULL, 356, 'hrac2', NULL, NULL),
(340, 7, 1, 25, 49, 149, NULL, NULL, NULL, 355, 'hrac2', NULL, NULL),
(341, 7, 1, 26, 24, 34, 3, 0, 24, 354, 'hrac2', NULL, NULL),
(342, 7, 1, 27, 26, 151, NULL, NULL, NULL, 353, 'hrac2', NULL, NULL),
(343, 7, 1, 28, 41, 40, NULL, NULL, NULL, 352, 'hrac2', NULL, NULL),
(344, 7, 1, 29, 37, 0, NULL, NULL, 37, 351, 'hrac2', NULL, NULL),
(345, 7, 1, 30, 42, 147, NULL, NULL, NULL, 350, 'hrac2', NULL, NULL),
(346, 7, 1, 31, 30, 153, NULL, NULL, NULL, 349, 'hrac2', NULL, NULL),
(347, 7, 1, 32, 39, 138, 3, 0, 39, 348, 'hrac2', NULL, NULL),
(348, 7, 2, 1, NULL, 39, NULL, NULL, NULL, 364, 'hrac1', NULL, NULL),
(349, 7, 2, 2, NULL, NULL, NULL, NULL, NULL, 365, 'hrac1', NULL, NULL),
(350, 7, 2, 3, NULL, NULL, NULL, NULL, NULL, 366, 'hrac1', NULL, NULL),
(351, 7, 2, 4, 46, 37, NULL, NULL, NULL, 367, 'hrac1', NULL, NULL),
(352, 7, 2, 5, NULL, NULL, NULL, NULL, NULL, 368, 'hrac1', NULL, NULL),
(353, 7, 2, 6, NULL, NULL, NULL, NULL, NULL, 369, 'hrac1', NULL, NULL),
(354, 7, 2, 7, NULL, 24, NULL, NULL, NULL, 370, 'hrac1', NULL, NULL),
(355, 7, 2, 8, NULL, NULL, NULL, NULL, NULL, 371, 'hrac1', NULL, NULL),
(356, 7, 2, 9, NULL, NULL, NULL, NULL, NULL, 371, 'hrac2', NULL, NULL),
(357, 7, 2, 10, NULL, NULL, NULL, NULL, NULL, 370, 'hrac2', NULL, NULL),
(358, 7, 2, 11, NULL, NULL, NULL, NULL, NULL, 369, 'hrac2', NULL, NULL),
(359, 7, 2, 12, NULL, 18, NULL, NULL, NULL, 368, 'hrac2', NULL, NULL),
(360, 7, 2, 13, NULL, NULL, NULL, NULL, NULL, 367, 'hrac2', NULL, NULL),
(361, 7, 2, 14, NULL, 17, NULL, NULL, NULL, 366, 'hrac2', NULL, NULL),
(362, 7, 2, 15, NULL, NULL, NULL, NULL, NULL, 365, 'hrac2', NULL, NULL),
(363, 7, 2, 16, NULL, 9, NULL, NULL, NULL, 364, 'hrac2', NULL, NULL),
(364, 7, 3, 1, NULL, NULL, NULL, NULL, NULL, 372, 'hrac1', NULL, NULL),
(365, 7, 3, 2, NULL, NULL, NULL, NULL, NULL, 373, 'hrac1', NULL, NULL),
(366, 7, 3, 3, NULL, NULL, NULL, NULL, NULL, 374, 'hrac1', NULL, NULL),
(367, 7, 3, 4, NULL, NULL, NULL, NULL, NULL, 375, 'hrac1', NULL, NULL),
(368, 7, 3, 5, NULL, NULL, NULL, NULL, NULL, 375, 'hrac2', NULL, NULL),
(369, 7, 3, 6, NULL, NULL, NULL, NULL, NULL, 374, 'hrac2', NULL, NULL),
(370, 7, 3, 7, NULL, NULL, NULL, NULL, NULL, 373, 'hrac2', NULL, NULL),
(371, 7, 3, 8, NULL, NULL, NULL, NULL, NULL, 372, 'hrac2', NULL, NULL),
(372, 7, 4, 1, NULL, NULL, NULL, NULL, NULL, 376, 'hrac1', NULL, NULL),
(373, 7, 4, 2, NULL, NULL, NULL, NULL, NULL, 377, 'hrac1', NULL, NULL),
(374, 7, 4, 3, NULL, NULL, NULL, NULL, NULL, 377, 'hrac2', NULL, NULL),
(375, 7, 4, 4, NULL, NULL, NULL, NULL, NULL, 376, 'hrac2', NULL, NULL),
(376, 7, 5, 1, NULL, NULL, NULL, NULL, NULL, 378, 'hrac1', NULL, NULL),
(377, 7, 5, 2, NULL, NULL, NULL, NULL, NULL, 378, 'hrac2', NULL, NULL),
(378, 7, 6, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktura tabulky `uzivatele`
--

CREATE TABLE IF NOT EXISTS `uzivatele` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jmeno` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` char(64) NOT NULL,
  `role` enum('user','admin','stat_editor') NOT NULL DEFAULT 'user',
  `must_change_pw` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=68 ;

--
-- Vypisuji data pro tabulku `uzivatele`
--

INSERT INTO `uzivatele` (`id`, `jmeno`, `username`, `password`, `role`, `must_change_pw`, `created_at`) VALUES
(1, 'Jakub Šebesta', 'sebesta', '$2y$10$.vPI5sBc/l78mnxKSt2Kp.M.5Oo2uS2nBZYTF2xoetjquL4.8WSUq', 'stat_editor', 1, '2025-05-18 16:45:48'),
(62, 'Dominik Beran', 'beran', '$2y$10$TniZc68HDbKQtp0X5VC0fex0a3Dljj1ez7yd4qTdALpFaic6rAge2', 'admin', 0, '2025-06-04 17:05:20'),
(65, 'Míša Beranová', 'beranova', '$2y$10$A6Ycqe0udMG7DX/zMnwAxunOycVKGkfKho8Nesm.DSVf/Bx6lEGv2', 'stat_editor', 0, '2025-08-26 18:48:55'),
(67, 'Lucka', 'lucka', '$2y$10$VIK1DR6Dp2JK2.5.xVu9kOzP5E3IFF34Hh/T6HVzTh/1KzaiNrIxS', 'stat_editor', 0, '2026-02-04 16:52:11');

-- --------------------------------------------------------

--
-- Zástupná struktura pro pohled `v_hraci_rocnik`
--
CREATE TABLE IF NOT EXISTS `v_hraci_rocnik` (
`rocnik_id` int(11)
,`hrac_id` int(11)
,`jmeno` varchar(100)
,`liga_id` int(11)
);
-- --------------------------------------------------------

--
-- Struktura tabulky `zapasy`
--

CREATE TABLE IF NOT EXISTS `zapasy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hrac1_id` int(11) DEFAULT NULL,
  `hrac2_id` int(11) DEFAULT NULL,
  `skore1` int(11) DEFAULT NULL,
  `skore2` int(11) DEFAULT NULL,
  `average_home` decimal(5,2) DEFAULT NULL,
  `average_away` decimal(5,2) DEFAULT NULL,
  `high_finish_home` smallint(6) NOT NULL DEFAULT 0,
  `high_finish_away` smallint(6) NOT NULL DEFAULT 0,
  `count_100p_home` smallint(6) NOT NULL DEFAULT 0,
  `count_100p_away` smallint(6) NOT NULL DEFAULT 0,
  `count_120p_home` smallint(6) NOT NULL DEFAULT 0,
  `count_120p_away` smallint(6) NOT NULL DEFAULT 0,
  `count_140p_home` smallint(6) NOT NULL DEFAULT 0,
  `count_140p_away` smallint(6) NOT NULL DEFAULT 0,
  `count_160p_home` smallint(6) NOT NULL DEFAULT 0,
  `count_160p_away` smallint(6) NOT NULL DEFAULT 0,
  `count_180_home` smallint(6) NOT NULL DEFAULT 0,
  `count_180_away` smallint(6) NOT NULL DEFAULT 0,
  `liga_id` int(11) DEFAULT NULL,
  `datum` date DEFAULT NULL,
  `rocnik_id` int(11) DEFAULT NULL,
  UNIQUE KEY `uq_id` (`id`),
  UNIQUE KEY `uq_pair` (`rocnik_id`,`liga_id`,`hrac1_id`,`hrac2_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=775 ;

--
-- Vypisuji data pro tabulku `zapasy`
--

INSERT INTO `zapasy` (`id`, `hrac1_id`, `hrac2_id`, `skore1`, `skore2`, `average_home`, `average_away`, `high_finish_home`, `high_finish_away`, `count_100p_home`, `count_100p_away`, `count_120p_home`, `count_120p_away`, `count_140p_home`, `count_140p_away`, `count_160p_home`, `count_160p_away`, `count_180_home`, `count_180_away`, `liga_id`, `datum`, `rocnik_id`) VALUES
(1, 1, 2, 7, 6, 59.76, 59.00, 120, 60, 9, 13, 0, 0, 0, 2, 0, 0, 0, 2, 1, '2025-05-15', 2),
(2, 1, 3, 7, 4, 57.71, 57.56, 32, 114, 10, 8, 0, 0, 2, 4, 0, 0, 0, 0, 1, '2025-05-15', 2),
(3, 1, 4, 7, 3, 60.43, 59.19, 100, 61, 7, 8, 0, 0, 2, 0, 1, 0, 1, 0, 1, '2025-05-15', 2),
(4, 1, 5, 7, 3, 57.92, 55.81, 72, 52, 8, 5, 0, 0, 2, 0, 0, 0, 0, 1, 1, '2025-05-15', 2),
(5, 1, 6, 7, 2, 56.42, 48.74, 72, 20, 5, 6, 0, 0, 3, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(6, 1, 7, 7, 3, 57.96, 54.09, 84, 60, 8, 6, 0, 0, 0, 0, 0, 0, 1, 0, 1, '2025-05-15', 2),
(7, 1, 8, 7, 4, 57.29, 53.91, 60, 81, 5, 4, 0, 0, 0, 3, 0, 0, 0, 0, 1, '2025-05-15', 2),
(8, 1, 9, 7, 0, 53.95, 43.36, 62, 0, 5, 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(9, 2, 3, 7, 4, 55.46, 56.50, 40, 20, 9, 12, 0, 0, 2, 4, 0, 0, 0, 0, 1, '2025-05-15', 2),
(10, 2, 4, 7, 4, 57.23, 54.88, 97, 38, 9, 7, 0, 0, 4, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(11, 2, 5, 6, 7, 53.52, 53.65, 54, 61, 7, 9, 0, 0, 3, 2, 0, 0, 0, 0, 1, '2025-05-15', 2),
(12, 2, 6, 7, 4, 47.30, 45.20, 53, 60, 6, 2, 0, 0, 1, 1, 1, 0, 0, 0, 1, '2025-05-15', 2),
(13, 2, 7, 7, 2, 56.20, 45.89, 62, 6, 11, 4, 0, 0, 1, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(14, 2, 8, 3, 7, 50.23, 52.58, 130, 100, 4, 10, 0, 0, 2, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(15, 2, 9, 1, 7, 47.87, 52.11, 32, 64, 4, 2, 0, 0, 1, 2, 0, 0, 1, 0, 1, '2025-05-15', 2),
(16, 3, 4, 3, 7, 57.10, 56.60, 72, 83, 10, 7, 0, 0, 3, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(17, 5, 3, 7, 5, 53.07, 52.64, 38, 73, 10, 8, 0, 0, 3, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(18, 3, 6, 7, 6, 53.58, 53.11, 88, 106, 9, 7, 0, 0, 4, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(19, 7, 3, 3, 7, 47.75, 50.42, 55, 34, 5, 7, 0, 0, 0, 2, 0, 0, 0, 0, 1, '2025-05-15', 2),
(20, 3, 8, 7, 3, 54.68, 47.67, 42, 20, 7, 4, 0, 0, 3, 1, 0, 0, 0, 0, 1, '2025-05-15', 2),
(21, 9, 3, 3, 7, 48.32, 53.90, 71, 75, 3, 11, 0, 0, 0, 4, 0, 0, 0, 0, 1, '2025-05-15', 2),
(22, 4, 5, 6, 7, 52.18, 53.16, 93, 57, 4, 8, 0, 0, 0, 3, 0, 0, 0, 0, 1, '2025-05-15', 2),
(23, 4, 6, 7, 1, 46.92, 44.62, 63, 32, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(24, 4, 7, 7, 2, 50.49, 44.22, 71, 24, 4, 3, 0, 0, 0, 1, 0, 0, 0, 0, 1, '2025-05-15', 2),
(25, 4, 8, 7, 4, 52.70, 47.15, 88, 72, 7, 4, 0, 0, 3, 0, 0, 1, 2, 0, 1, '2025-05-15', 2),
(26, 4, 9, 7, 6, 48.96, 43.18, 52, 72, 7, 2, 0, 0, 3, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(27, 5, 6, 7, 6, 49.78, 50.88, 40, 72, 11, 6, 0, 0, 1, 3, 0, 0, 0, 0, 1, '2025-05-15', 2),
(28, 7, 5, 3, 7, 43.33, 46.10, 31, 42, 5, 4, 0, 0, 1, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(29, 5, 8, 7, 1, 56.68, 50.32, 118, 54, 9, 5, 0, 0, 3, 1, 0, 0, 0, 1, 1, '2025-05-15', 2),
(30, 9, 5, 0, 7, 37.65, 55.96, 0, 54, 1, 5, 0, 0, 0, 3, 0, 0, 0, 1, 1, '2025-05-15', 2),
(31, 6, 7, 6, 7, 48.83, 45.82, 60, 40, 6, 9, 0, 0, 3, 1, 0, 0, 0, 0, 1, '2025-05-15', 2),
(32, 6, 8, 7, 4, 49.70, 48.70, 56, 48, 6, 3, 0, 0, 1, 4, 0, 0, 0, 0, 1, '2025-05-15', 2),
(33, 6, 9, 7, 2, 42.95, 39.84, 55, 40, 4, 2, 0, 0, 1, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(34, 7, 8, 0, 7, 46.08, 52.87, 0, 56, 4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(35, 7, 9, 7, 1, 46.76, 46.18, 120, 2, 3, 4, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(36, 8, 9, 7, 3, 50.41, 45.95, 50, 72, 8, 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, '2025-05-15', 2),
(37, 10, 11, 7, 1, 47.05, 45.46, 116, 35, 6, 6, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(38, 10, 12, 6, 7, 42.71, 42.72, 69, 39, 3, 1, 0, 0, 1, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(39, 10, 13, 7, 4, 44.20, 43.31, 40, 25, 7, 2, 0, 0, 1, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(40, 10, 14, 7, 2, 50.88, 47.83, 54, 60, 3, 5, 0, 0, 1, 0, 1, 0, 0, 0, 2, '2025-05-15', 2),
(41, 10, 15, 7, 1, 43.28, 40.60, 64, 8, 1, 2, 0, 0, 1, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(42, 10, 16, 7, 6, 45.73, 46.11, 50, 70, 8, 10, 0, 0, 1, 1, 0, 0, 0, 0, 2, '2025-05-15', 2),
(43, 10, 17, 7, 2, 46.44, 44.49, 96, 32, 2, 2, 0, 0, 0, 0, 0, 1, 0, 0, 2, '2025-05-15', 2),
(44, 10, 18, 7, 3, 47.40, 43.80, 68, 62, 10, 3, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(45, 11, 12, 7, 2, 48.80, 46.46, 60, 97, 5, 3, 0, 0, 1, 0, 0, 1, 0, 0, 2, '2025-05-15', 2),
(46, 11, 13, 7, 1, 46.95, 45.48, 50, 40, 4, 8, 0, 0, 1, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(47, 11, 14, 7, 5, 52.55, 47.58, 48, 84, 8, 9, 0, 0, 3, 1, 0, 0, 0, 1, 2, '2025-05-15', 2),
(48, 11, 15, 7, 3, 48.29, 48.05, 42, 40, 6, 6, 0, 0, 1, 0, 0, 0, 1, 1, 2, '2025-05-15', 2),
(49, 11, 16, 7, 0, 49.54, 40.66, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(50, 11, 17, 7, 2, 54.36, 46.03, 46, 48, 10, 5, 0, 0, 1, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(51, 11, 18, 7, 0, 50.34, 49.07, 60, 0, 5, 7, 0, 0, 1, 1, 0, 0, 0, 0, 2, '2025-05-15', 2),
(52, 12, 13, 5, 7, 40.82, 42.16, 32, 142, 2, 2, 0, 0, 0, 1, 0, 0, 0, 0, 2, '2025-05-15', 2),
(53, 12, 14, 7, 6, 45.93, 45.32, 40, 77, 4, 7, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(54, 12, 15, 7, 3, 46.27, 45.55, 50, 64, 1, 8, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(55, 12, 16, 7, 4, 40.17, 39.46, 55, 40, 2, 8, 0, 0, 0, 2, 0, 0, 0, 0, 2, '2025-05-15', 2),
(56, 12, 17, 7, 5, 42.21, 42.37, 58, 74, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(57, 12, 18, 7, 5, 35.76, 35.81, 22, 32, 1, 3, 0, 0, 0, 1, 0, 0, 0, 0, 2, '2025-05-15', 2),
(58, 13, 14, 7, 4, 45.23, 41.86, 81, 58, 3, 2, 0, 0, 1, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(59, 13, 15, 3, 7, 41.91, 45.76, 36, 55, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(60, 13, 16, 7, 5, 45.94, 41.20, 64, 40, 5, 6, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(61, 13, 17, 6, 7, 43.19, 42.93, 38, 120, 6, 6, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(62, 13, 18, 7, 1, 48.63, 44.50, 105, 4, 5, 1, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(63, 14, 15, 7, 4, 39.96, 38.32, 40, 40, 3, 3, 0, 0, 0, 0, 1, 0, 0, 0, 2, '2025-05-15', 2),
(64, 14, 16, 7, 4, 40.40, 38.99, 40, 40, 6, 7, 0, 0, 0, 2, 0, 0, 0, 0, 2, '2025-05-15', 2),
(65, 14, 17, 7, 4, 41.65, 40.98, 60, 45, 8, 5, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(66, 14, 18, 7, 2, 42.55, 38.22, 40, 31, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(67, 15, 16, 7, 0, 44.18, 40.66, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(68, 15, 17, 7, 3, 42.52, 40.65, 42, 8, 3, 4, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(69, 15, 18, 7, 0, 48.48, 43.18, 40, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(70, 16, 17, 7, 6, 36.91, 37.07, 55, 20, 3, 3, 0, 0, 1, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(71, 16, 18, 7, 4, 41.28, 40.40, 54, 77, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(72, 17, 18, 7, 2, 48.68, 45.68, 89, 89, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 2),
(73, 19, 20, 7, 2, 42.41, 40.10, 48, 20, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(74, 19, 21, 7, 6, 48.89, 48.13, 53, 66, 8, 4, 0, 0, 3, 3, 0, 0, 0, 0, 3, '2025-05-15', 2),
(75, 19, 22, 1, 7, 51.79, 54.96, 40, 40, 6, 6, 0, 0, 2, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(76, 19, 23, 7, 1, 50.90, 42.57, 60, 45, 2, 2, 0, 0, 1, 0, 1, 0, 0, 0, 3, '2025-05-15', 2),
(77, 19, 24, 7, 2, 49.48, 43.49, 77, 60, 3, 1, 0, 0, 1, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(78, 19, 25, 7, 0, 48.44, 35.30, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(79, 19, 26, 7, 2, 47.19, 40.01, 66, 24, 6, 4, 0, 0, 1, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(80, 19, 27, 7, 0, 48.44, 33.06, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(81, 20, 21, 7, 4, 44.96, 43.37, 52, 64, 2, 6, 0, 0, 0, 2, 0, 0, 0, 0, 3, '2025-05-15', 2),
(82, 20, 22, 0, 7, 34.49, 45.74, 0, 67, 1, 5, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(83, 20, 23, 7, 3, 41.61, 39.50, 39, 57, 4, 2, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(84, 20, 24, 7, 6, 41.03, 40.13, 50, 59, 2, 6, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(85, 20, 25, 4, 7, 38.32, 35.92, 40, 38, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(86, 20, 26, 7, 5, 43.83, 42.81, 48, 32, 2, 7, 0, 0, 0, 1, 0, 0, 0, 0, 3, '2025-05-15', 2),
(87, 20, 27, 7, 1, 34.94, 31.06, 40, 31, 2, 3, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(88, 21, 22, 2, 7, 44.11, 52.43, 77, 107, 3, 5, 0, 0, 1, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(89, 21, 23, 7, 5, 38.01, 38.27, 100, 28, 6, 6, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(90, 21, 24, 7, 3, 41.78, 40.11, 110, 41, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(91, 21, 25, 7, 3, 37.97, 34.10, 48, 22, 6, 3, 0, 0, 2, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(92, 21, 26, 7, 3, 41.95, 38.82, 78, 55, 4, 3, 0, 0, 2, 0, 0, 0, 1, 0, 3, '2025-05-15', 2),
(93, 21, 27, 7, 5, 39.92, 37.77, 40, 61, 6, 3, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(94, 22, 23, 7, 6, 49.70, 46.82, 127, 52, 3, 5, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(95, 22, 24, 7, 0, 54.23, 42.24, 76, 0, 6, 2, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(96, 22, 25, 7, 0, 51.03, 35.30, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(97, 22, 26, 7, 3, 49.09, 47.32, 72, 40, 5, 5, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(98, 22, 27, 7, 0, 51.03, 33.06, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(99, 23, 24, 7, 6, 35.37, 36.85, 24, 40, 3, 2, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(100, 23, 25, 7, 2, 38.43, 35.88, 56, 79, 7, 2, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(101, 23, 26, 7, 5, 35.86, 35.47, 60, 72, 6, 3, 0, 0, 0, 1, 0, 0, 0, 0, 3, '2025-05-15', 2),
(102, 23, 27, 7, 1, 36.62, 32.81, 47, 45, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(103, 24, 25, 7, 0, 38.69, 35.30, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(104, 24, 26, 7, 3, 36.51, 34.90, 60, 32, 3, 4, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(105, 24, 27, 7, 5, 31.50, 31.33, 20, 52, 4, 3, 0, 0, 1, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(106, 25, 26, 3, 7, 35.30, 38.92, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(107, 25, 27, 3, 7, 35.30, 33.06, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(108, 26, 27, 7, 3, 33.13, 32.34, 32, 38, 2, 4, 0, 0, 1, 0, 0, 0, 0, 0, 3, '2025-05-15', 2),
(109, 28, 29, 6, 7, 45.65, 47.71, 59, 60, 6, 8, 0, 0, 0, 1, 0, 0, 0, 0, 4, '2025-05-15', 2),
(110, 28, 30, 7, 3, 50.80, 49.62, 60, 60, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(111, 28, 31, 7, 1, 36.98, 34.48, 40, 34, 3, 1, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(112, 28, 32, 7, 4, 38.82, 34.33, 52, 20, 7, 2, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(113, 28, 33, 7, 0, 49.63, 42.09, 68, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(114, 28, 34, 7, 4, 47.23, 40.40, 40, 38, 7, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(115, 28, 35, 7, 3, 36.98, 33.77, 49, 55, 4, 2, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(116, 28, 36, 7, 0, 43.73, 33.80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(117, 29, 30, 5, 7, 47.40, 47.99, 48, 60, 6, 4, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(118, 29, 31, 7, 0, 55.67, 45.87, 110, 0, 10, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(119, 29, 32, 7, 1, 45.89, 38.57, 59, 24, 2, 0, 0, 0, 2, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(120, 29, 33, 7, 3, 39.65, 38.16, 40, 42, 8, 3, 0, 0, 1, 2, 0, 0, 0, 0, 4, '2025-05-15', 2),
(121, 29, 34, 7, 6, 46.19, 45.39, 90, 48, 6, 5, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(122, 29, 35, 7, 2, 41.65, 35.57, 86, 61, 5, 0, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(123, 29, 36, 7, 1, 38.89, 35.74, 40, 2, 2, 1, 0, 0, 1, 1, 0, 0, 1, 0, 4, '2025-05-15', 2),
(124, 30, 31, 7, 6, 41.87, 40.21, 98, 76, 3, 2, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(125, 30, 32, 7, 0, 39.55, 34.78, 107, 0, 2, 2, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(126, 30, 33, 7, 2, 45.48, 39.48, 72, 38, 6, 2, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(127, 30, 34, 7, 4, 39.23, 37.69, 40, 53, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(128, 30, 35, 7, 1, 45.18, 40.36, 50, 6, 3, 2, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(129, 30, 36, 7, 0, 44.13, 33.80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(130, 31, 32, 7, 4, 33.94, 34.37, 62, 40, 3, 2, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(131, 31, 33, 7, 2, 32.72, 32.13, 61, 30, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(132, 31, 34, 7, 5, 31.71, 30.75, 20, 58, 3, 1, 0, 0, 1, 1, 0, 0, 0, 0, 4, '2025-05-15', 2),
(133, 31, 35, 7, 6, 34.78, 34.61, 50, 40, 4, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(134, 31, 36, 7, 0, 36.24, 33.80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(135, 32, 33, 7, 6, 32.11, 33.22, 48, 46, 2, 1, 0, 0, 0, 1, 0, 0, 0, 0, 4, '2025-05-15', 2),
(136, 32, 34, 7, 3, 35.98, 35.54, 40, 16, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(137, 32, 35, 3, 7, 39.28, 43.01, 55, 78, 3, 2, 0, 0, 0, 1, 0, 0, 0, 0, 4, '2025-05-15', 2),
(138, 32, 36, 7, 0, 35.63, 33.80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(139, 33, 34, 7, 5, 35.80, 34.16, 50, 18, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(140, 33, 35, 7, 1, 30.05, 29.98, 55, 21, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(141, 33, 36, 7, 6, 29.22, 29.39, 54, 40, 1, 2, 0, 0, 0, 1, 0, 0, 0, 0, 4, '2025-05-15', 2),
(142, 34, 35, 7, 6, 35.83, 36.82, 60, 93, 0, 4, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(143, 34, 36, 7, 5, 37.97, 36.28, 32, 44, 3, 4, 0, 0, 0, 0, 0, 1, 0, 0, 4, '2025-05-15', 2),
(144, 35, 36, 7, 0, 36.30, 33.80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 2),
(145, 37, 38, 7, 6, 41.33, 41.93, 0, 75, 5, 3, 0, 0, 1, 2, 0, 0, 0, 0, 5, '2025-05-15', 2),
(146, 37, 39, 7, 2, 38.42, 35.77, 24, 17, 4, 3, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(147, 37, 40, 7, 5, 40.49, 39.39, 42, 58, 4, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(148, 37, 41, 7, 4, 41.60, 42.82, 42, 36, 6, 5, 0, 0, 3, 2, 0, 0, 0, 0, 5, '2025-05-15', 2),
(149, 37, 42, 7, 6, 35.71, 36.67, 51, 34, 2, 0, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(150, 37, 43, 7, 0, 34.61, 31.14, 64, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(151, 37, 44, 7, 0, 35.79, 33.99, 56, 0, 2, 1, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(152, 37, 45, 7, 1, 42.21, 36.87, 32, 50, 3, 2, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(153, 38, 39, 7, 0, 37.18, 31.74, 40, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(154, 38, 40, 7, 2, 38.64, 37.44, 40, 70, 4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(155, 38, 41, 7, 1, 38.00, 36.59, 36, 49, 2, 3, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(156, 38, 42, 7, 2, 41.34, 38.04, 72, 48, 4, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(157, 38, 43, 7, 0, 38.80, 35.20, 20, 0, 5, 0, 0, 0, 0, 1, 0, 0, 0, 0, 5, '2025-05-15', 2),
(158, 38, 44, 7, 1, 36.20, 32.00, 35, 14, 6, 1, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(159, 38, 45, 7, 3, 41.25, 38.24, 39, 48, 5, 4, 0, 0, 2, 1, 0, 0, 0, 0, 5, '2025-05-15', 2),
(160, 39, 40, 4, 7, 31.38, 34.13, 37, 60, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(161, 39, 41, 7, 6, 43.27, 42.57, 92, 40, 4, 6, 0, 0, 1, 1, 0, 1, 0, 0, 5, '2025-05-15', 2),
(162, 39, 42, 7, 4, 29.41, 29.33, 49, 42, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(163, 39, 43, 7, 6, 26.86, 25.40, 20, 48, 3, 0, 0, 0, 0, 1, 0, 0, 0, 0, 5, '2025-05-15', 2),
(164, 39, 44, 7, 2, 32.63, 30.63, 40, 6, 3, 1, 0, 0, 0, 1, 0, 0, 1, 0, 5, '2025-05-15', 2),
(165, 39, 45, 7, 2, 42.14, 37.97, 75, 18, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(166, 40, 41, 7, 6, 37.44, 40.53, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(167, 40, 42, 7, 3, 45.12, 43.38, 84, 53, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(168, 40, 43, 7, 2, 30.18, 29.42, 40, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(169, 40, 44, 7, 3, 38.48, 34.53, 88, 20, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(170, 40, 45, 7, 1, 39.66, 34.82, 96, 6, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(171, 41, 42, 7, 4, 43.86, 36.75, 50, 27, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(172, 41, 43, 7, 1, 38.54, 35.37, 38, 19, 7, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(173, 41, 44, 7, 1, 36.74, 33.33, 40, 26, 14, 1, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(174, 41, 45, 7, 0, 42.60, 38.00, 54, 0, 3, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(175, 42, 43, 7, 3, 32.73, 31.66, 46, 41, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(176, 42, 44, 7, 1, 36.40, 32.48, 51, 4, 3, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(177, 42, 45, 7, 1, 36.83, 35.49, 32, 40, 1, 2, 0, 0, 0, 1, 0, 0, 0, 0, 5, '2025-05-15', 2),
(178, 43, 44, 4, 7, 28.46, 28.22, 30, 23, 0, 3, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(179, 43, 45, 2, 7, 28.33, 30.69, 40, 56, 1, 7, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 2),
(180, 44, 45, 6, 7, 30.78, 30.21, 39, 60, 1, 7, 0, 0, 0, 1, 0, 1, 0, 0, 5, '2025-05-15', 2),
(181, 1, 2, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(182, 1, 14, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(183, 1, 5, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(184, 1, 6, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(185, 1, 7, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(186, 1, 8, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(187, 1, 13, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(188, 2, 14, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(189, 2, 5, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(190, 2, 6, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(191, 2, 7, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(192, 2, 8, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(193, 2, 13, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(194, 14, 5, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(195, 14, 6, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(196, 14, 7, 6, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(197, 14, 8, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(198, 14, 13, 3, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(199, 5, 6, 2, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(200, 5, 7, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(201, 5, 8, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(202, 5, 13, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(203, 6, 7, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(204, 6, 8, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(205, 6, 13, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(206, 7, 8, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(207, 7, 13, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(208, 8, 13, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-15', 1),
(209, 3, 9, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(210, 3, 24, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(211, 3, 12, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(212, 3, 15, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(213, 3, 21, 7, 1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(214, 3, 17, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(215, 3, 18, 3, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(216, 9, 24, 7, 1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(217, 9, 12, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(218, 9, 15, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(219, 9, 21, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(220, 9, 17, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(221, 9, 18, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(222, 24, 12, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(223, 24, 15, 1, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(224, 24, 21, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(225, 24, 17, 0, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(226, 24, 18, 0, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(227, 12, 15, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(228, 12, 21, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(229, 12, 17, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(230, 12, 18, 6, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(231, 15, 21, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(232, 15, 17, 6, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(233, 15, 18, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(234, 21, 17, 3, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(235, 21, 18, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(236, 17, 18, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-05-15', 1),
(237, 10, 20, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(238, 10, 16, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(239, 10, 119, 7, 1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(240, 10, 35, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(241, 10, 31, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(242, 10, 26, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(243, 10, 27, 7, 1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(244, 20, 16, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(245, 20, 119, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(246, 20, 35, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(247, 20, 31, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(248, 20, 26, 6, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(249, 20, 27, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(250, 16, 119, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(251, 16, 35, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(252, 16, 31, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(253, 16, 26, 7, 1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(254, 16, 27, 7, 1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(255, 119, 35, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(256, 119, 31, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(257, 119, 26, 3, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(258, 119, 27, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(259, 35, 31, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(260, 35, 26, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(261, 35, 27, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(262, 31, 26, 1, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(263, 31, 27, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(264, 26, 27, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-05-15', 1),
(265, 25, 23, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(266, 25, 44, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(267, 25, 32, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(268, 25, 33, 6, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(269, 25, 34, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(270, 25, 130, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(271, 25, 36, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(272, 23, 44, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(273, 23, 32, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(274, 23, 33, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(275, 23, 34, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(276, 23, 130, 7, 5, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(277, 23, 36, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(278, 44, 32, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(279, 44, 33, 6, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(280, 44, 34, 2, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(281, 44, 130, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(282, 44, 36, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(283, 32, 33, 3, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(284, 32, 34, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(285, 32, 130, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(286, 32, 36, 6, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(287, 33, 34, 4, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(288, 33, 130, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(289, 33, 36, 3, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(290, 34, 130, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(291, 34, 36, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(292, 130, 36, 2, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-05-15', 1),
(293, 22, 19, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(294, 22, 29, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(295, 22, 30, 7, 1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(296, 22, 38, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(297, 22, 137, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(298, 22, 28, 7, 1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(299, 22, 45, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(300, 19, 29, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(301, 19, 30, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(302, 19, 38, 7, 1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(303, 19, 137, 7, 4, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(304, 19, 28, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(305, 19, 45, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(306, 29, 30, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(307, 29, 38, 7, 6, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(308, 29, 137, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(309, 29, 28, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(310, 29, 45, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(311, 30, 38, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(312, 30, 137, 7, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(313, 30, 28, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(314, 30, 45, 7, 2, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(315, 38, 137, 3, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(316, 38, 28, 1, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(317, 38, 45, 0, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(318, 137, 28, 5, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(319, 137, 45, 3, 7, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(320, 28, 45, 7, 3, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-05-15', 1),
(522, 3, 46, 7, 1, 53.49, 49.06, 103, 16, 10, 6, 0, 0, 1, 1, 0, 0, 0, 0, 1, NULL, 3),
(523, 1, 5, 7, 3, 57.79, 53.26, 40, 53, 8, 9, 0, 0, 0, 0, 1, 0, 1, 0, 1, '2025-10-10', 3),
(524, 2, 3, 7, 4, 56.77, 54.25, 55, 58, 9, 13, 0, 0, 3, 1, 0, 0, 0, 0, 1, '2025-12-30', 3),
(525, 10, 11, 2, 7, 52.51, 60.50, 72, 52, 4, 8, 0, 0, 1, 3, 0, 0, 0, 0, 1, '2025-12-23', 3),
(526, 4, 8, 0, 7, 0.00, 50.55, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-01-03', 3),
(527, 7, 46, 7, 1, 52.18, 51.71, 48, 68, 6, 7, 0, 0, 1, 0, 0, 0, 0, 0, 1, '2025-11-19', 3),
(528, 2, 5, 7, 1, 52.68, 49.84, 52, 20, 7, 4, 0, 0, 4, 0, 0, 0, 1, 0, 1, NULL, 3),
(529, 1, 10, 7, 2, 64.93, 54.01, 95, 40, 9, 3, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-11-26', 3),
(530, 3, 8, 7, 3, 50.38, 48.73, 64, 59, 11, 5, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-10-24', 3),
(531, 7, 11, 2, 7, 47.87, 52.61, 25, 104, 4, 5, 0, 0, 1, 0, 0, 0, 0, 0, 1, '2025-11-19', 3),
(532, 4, 46, 0, 7, 0.00, 53.98, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-01-03', 3),
(533, 5, 10, 7, 2, 50.55, 48.07, 56, 40, 6, 2, 0, 0, 2, 0, 0, 0, 0, 0, 1, '2025-12-23', 3),
(534, 2, 8, 7, 3, 57.15, 50.16, 85, 40, 13, 5, 0, 0, 1, 0, 0, 0, 1, 0, 1, '2025-11-20', 3),
(535, 1, 7, 7, 2, 57.89, 50.08, 40, 16, 9, 3, 0, 0, 2, 2, 0, 0, 0, 0, 1, NULL, 3),
(536, 4, 11, 0, 7, 0.00, 57.89, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-01-03', 3),
(537, 5, 8, 2, 7, 42.99, 44.43, 41, 70, 3, 3, 0, 0, 0, 2, 0, 0, 0, 0, 1, '2025-12-23', 3),
(538, 7, 10, 4, 7, 42.33, 43.96, 38, 40, 4, 6, 0, 0, 1, 0, 0, 0, 0, 0, 1, '2025-12-23', 3),
(539, 2, 46, 7, 2, 58.65, 56.35, 96, 40, 11, 9, 0, 0, 0, 2, 0, 0, 0, 0, 1, NULL, 3),
(540, 1, 4, 7, 0, 59.30, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-01-03', 3),
(541, 3, 11, 6, 7, 57.19, 54.15, 147, 68, 11, 5, 0, 0, 4, 1, 0, 0, 0, 0, 1, '2025-10-29', 3),
(542, 5, 7, 2, 7, 41.82, 46.07, 40, 80, 2, 5, 0, 0, 0, 1, 0, 0, 0, 0, 1, '2026-01-02', 3),
(543, 8, 46, 6, 7, 54.81, 57.92, 76, 95, 7, 13, 0, 0, 1, 2, 0, 0, 1, 0, 1, '2025-10-08', 3),
(544, 4, 10, 0, 7, 0.00, 50.71, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-01-03', 3),
(545, 2, 11, 7, 4, 66.30, 65.79, 80, 32, 14, 12, 0, 0, 3, 2, 0, 0, 0, 0, 1, '2025-10-29', 3),
(546, 1, 3, 5, 7, 56.69, 58.50, 96, 60, 7, 12, 0, 0, 0, 3, 0, 0, 0, 0, 1, '2025-12-30', 3),
(547, 5, 46, 7, 6, 49.75, 50.59, 46, 80, 10, 6, 0, 0, 1, 0, 0, 0, 0, 0, 1, '2025-12-23', 3),
(548, 4, 5, 0, 7, 0.00, 49.31, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-01-03', 3),
(549, 4, 7, 0, 7, 0.00, 47.78, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-01-03', 3),
(550, 8, 11, 4, 7, 53.69, 51.83, 54, 40, 9, 6, 0, 0, 3, 0, 0, 0, 1, 1, 1, '2025-12-04', 3),
(551, 3, 10, 7, 3, 55.20, 51.11, 42, 38, 7, 5, 0, 0, 1, 0, 0, 0, 0, 1, 1, '2025-11-26', 3),
(552, 1, 2, 1, 7, 53.47, 63.14, 64, 60, 3, 9, 0, 0, 0, 0, 0, 0, 0, 1, 1, '2025-12-30', 3),
(553, 11, 46, 7, 5, 57.55, 55.89, 114, 52, 11, 9, 0, 0, 1, 1, 1, 0, 0, 0, 1, '2025-12-23', 3),
(554, 3, 7, 7, 4, 47.43, 45.98, 110, 55, 8, 5, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-10-10', 3),
(555, 1, 8, 7, 2, 60.42, 48.15, 40, 35, 9, 5, 0, 0, 2, 0, 0, 0, 0, 0, 1, '2025-10-24', 3),
(556, 2, 10, 7, 2, 62.43, 52.07, 116, 32, 13, 6, 0, 0, 2, 0, 0, 0, 0, 0, 1, '2025-11-26', 3),
(557, 5, 11, 3, 7, 52.79, 62.70, 97, 100, 7, 12, 0, 0, 1, 4, 0, 0, 0, 0, 1, '2025-12-23', 3),
(558, 3, 4, 7, 0, 53.86, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-01-03', 3),
(559, 1, 46, 7, 1, 63.22, 58.79, 74, 36, 8, 12, 0, 0, 1, 1, 0, 0, 0, 0, 1, '2025-11-11', 3),
(560, 2, 7, 7, 4, 59.85, 50.38, 110, 64, 10, 2, 0, 0, 1, 0, 0, 1, 1, 1, 1, '2025-11-19', 3),
(561, 8, 10, 7, 3, 54.24, 52.22, 74, 114, 6, 6, 0, 0, 2, 2, 1, 0, 1, 0, 1, '2025-12-29', 3),
(562, 3, 5, 4, 7, 54.45, 53.51, 58, 100, 7, 14, 0, 0, 1, 1, 0, 0, 0, 0, 1, NULL, 3),
(563, 1, 11, 7, 4, 60.01, 57.96, 56, 72, 8, 5, 0, 0, 1, 2, 0, 0, 0, 0, 1, '2025-11-19', 3),
(564, 2, 4, 7, 0, 59.62, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-01-03', 3),
(565, 10, 46, 3, 7, 51.72, 51.50, 164, 52, 3, 8, 0, 0, 2, 0, 1, 0, 0, 0, 1, '2025-12-20', 3),
(566, 7, 8, 5, 7, 47.37, 50.19, 36, 57, 2, 5, 0, 0, 1, 1, 1, 0, 0, 0, 1, '2025-12-23', 3),
(567, 13, 20, 3, 7, 42.88, 43.06, 70, 40, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-10-31', 3),
(568, 15, 19, 7, 4, 47.57, 43.05, 64, 32, 6, 3, 0, 0, 0, 1, 0, 0, 0, 0, 2, '2026-01-02', 3),
(569, 6, 52, 7, 3, 49.61, 46.60, 72, 42, 3, 5, 0, 0, 3, 0, 0, 0, 0, 0, 2, '2025-10-29', 3),
(570, 14, 22, 1, 7, 47.66, 58.35, 51, 76, 1, 7, 0, 0, 1, 1, 0, 0, 0, 0, 2, '2026-01-08', 3),
(571, 19, 20, 7, 5, 41.80, 41.80, 55, 38, 6, 2, 0, 0, 0, 0, 0, 1, 0, 0, 2, NULL, 3),
(572, 9, 13, 7, 3, 46.63, 43.37, 61, 20, 2, 4, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-10-24', 3),
(573, 6, 15, 4, 7, 42.64, 43.47, 32, 32, 7, 4, 0, 0, 0, 0, 0, 1, 0, 0, 2, '2025-10-29', 3),
(574, 12, 22, 1, 7, 41.95, 50.91, 8, 48, 1, 2, 0, 0, 0, 0, 0, 1, 0, 0, 2, NULL, 3),
(575, 14, 52, 7, 6, 42.38, 42.65, 68, 50, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2026-01-03', 3),
(576, 9, 20, 7, 2, 39.29, 38.76, 90, 24, 2, 2, 0, 0, 0, 0, 1, 0, 0, 0, 2, '2025-10-29', 3),
(577, 6, 19, 2, 7, 40.36, 42.29, 56, 75, 6, 2, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-10-28', 3),
(578, 13, 22, 0, 7, 44.45, 52.57, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2026-01-09', 3),
(579, 12, 15, 7, 5, 43.03, 42.36, 72, 32, 3, 2, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-10-14', 3),
(580, 20, 22, 3, 7, 44.62, 44.63, 32, 32, 1, 9, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-11-26', 3),
(581, 6, 14, 7, 4, 52.64, 48.52, 71, 54, 4, 3, 0, 0, 1, 2, 0, 0, 0, 0, 2, '2026-01-10', 3),
(582, 22, 52, 7, 6, 57.20, 52.85, 100, 76, 5, 7, 0, 0, 0, 1, 0, 0, 0, 0, 2, '2025-11-08', 3),
(583, 6, 22, 3, 7, 53.60, 57.34, 60, 92, 5, 4, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-11-26', 3),
(584, 9, 14, 7, 3, 43.30, 41.42, 40, 48, 2, 2, 0, 0, 0, 1, 0, 0, 0, 0, 2, '2026-01-09', 3),
(585, 19, 52, 7, 6, 50.66, 48.91, 52, 40, 10, 4, 0, 0, 1, 2, 1, 0, 0, 0, 2, '2025-10-15', 3),
(586, 12, 13, 4, 7, 39.56, 42.90, 112, 58, 2, 5, 0, 0, 0, 0, 0, 2, 0, 0, 2, NULL, 3),
(587, 15, 20, 7, 5, 40.79, 39.77, 101, 75, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-12-19', 3),
(588, 6, 9, 7, 0, 50.83, 40.28, 72, 0, 6, 0, 0, 0, 1, 0, 0, 0, 0, 0, 2, '2025-12-30', 3),
(589, 19, 22, 2, 7, 45.69, 47.40, 87, 40, 2, 2, 0, 0, 1, 0, 0, 0, 0, 0, 2, NULL, 3),
(590, 13, 14, 4, 7, 46.87, 47.02, 52, 60, 2, 3, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2026-01-03', 3),
(591, 15, 52, 2, 7, 53.80, 52.56, 130, 109, 6, 5, 0, 0, 0, 1, 1, 0, 0, 0, 2, '2025-12-23', 3),
(592, 12, 20, 7, 2, 36.49, 34.35, 63, 38, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-11-05', 3),
(593, 9, 19, 5, 7, 48.11, 50.02, 50, 48, 3, 8, 0, 0, 0, 2, 0, 0, 0, 0, 2, '2025-11-05', 3),
(594, 6, 13, 7, 4, 48.51, 45.15, 59, 89, 8, 1, 0, 0, 0, 0, 0, 1, 0, 0, 2, '2025-09-19', 3),
(595, 15, 22, 2, 7, 49.87, 56.84, 32, 50, 1, 7, 0, 0, 0, 2, 0, 1, 0, 0, 2, '2025-10-14', 3),
(596, 20, 52, 4, 7, 40.31, 43.64, 40, 40, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 2, NULL, 3),
(597, 13, 19, 1, 7, 41.26, 47.84, 18, 60, 2, 5, 0, 0, 0, 1, 0, 0, 0, 0, 2, NULL, 3),
(598, 9, 15, 6, 7, 47.20, 51.09, 40, 119, 5, 4, 0, 0, 0, 0, 0, 1, 0, 0, 2, '2025-10-29', 3),
(599, 6, 12, 7, 2, 48.87, 43.76, 64, 40, 4, 0, 0, 0, 1, 0, 0, 0, 0, 0, 2, '2025-12-30', 3),
(600, 14, 20, 7, 5, 41.15, 41.46, 46, 40, 6, 3, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-11-21', 3),
(601, 13, 15, 1, 7, 48.13, 49.20, 14, 91, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-12-09', 3),
(602, 12, 19, 7, 3, 47.06, 45.25, 81, 17, 2, 1, 0, 0, 0, 0, 0, 1, 0, 0, 2, '2025-10-28', 3),
(603, 9, 52, 0, 7, 36.87, 37.58, 0, 40, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-11-29', 3),
(604, 13, 52, 2, 7, 45.01, 42.92, 49, 63, 1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 2, NULL, 3),
(605, 14, 19, 3, 7, 44.41, 44.51, 63, 43, 3, 4, 0, 0, 0, 1, 0, 0, 0, 0, 2, '2026-01-08', 3),
(606, 9, 22, 3, 7, 44.92, 47.86, 24, 52, 3, 4, 0, 0, 0, 1, 1, 0, 0, 0, 2, '2025-12-20', 3),
(607, 6, 20, 7, 1, 52.32, 48.34, 90, 18, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-11-28', 3),
(608, 12, 52, 3, 7, 43.65, 45.79, 24, 59, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-12-03', 3),
(609, 14, 15, 1, 7, 47.09, 54.38, 50, 40, 5, 3, 0, 0, 1, 1, 0, 0, 0, 0, 2, '2025-12-23', 3),
(610, 9, 12, 1, 7, 40.21, 45.52, 2, 60, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 2, '2025-10-24', 3),
(611, 12, 14, 7, 4, 46.36, 41.77, 51, 47, 5, 3, 0, 0, 0, 1, 0, 0, 0, 0, 2, '2025-10-31', 3),
(612, 17, 21, 1, 7, 40.19, 43.07, 61, 40, 2, 6, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-11-20', 3),
(613, 16, 24, 7, 6, 41.24, 39.86, 90, 56, 6, 5, 0, 0, 2, 1, 0, 0, 0, 0, 3, '2025-12-05', 3),
(614, 23, 26, 7, 3, 37.30, 35.10, 40, 24, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-11-20', 3),
(615, 18, 50, 7, 4, 40.59, 39.02, 57, 10, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 3, NULL, 3),
(616, 28, 29, 5, 7, 47.60, 48.79, 40, 56, 7, 6, 0, 0, 0, 3, 0, 0, 0, 0, 3, '2025-12-23', 3),
(617, 21, 24, 7, 2, 33.61, 32.33, 40, 20, 2, 3, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-11-13', 3),
(618, 17, 26, 7, 1, 35.49, 33.18, 40, 34, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2026-01-09', 3),
(619, 16, 50, 4, 7, 40.29, 39.12, 40, 32, 13, 1, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-11-13', 3),
(620, 23, 29, 5, 7, 41.13, 40.41, 74, 64, 4, 3, 0, 0, 0, 0, 0, 0, 0, 0, 3, NULL, 3),
(621, 18, 28, 5, 7, 39.23, 43.87, 76, 36, 1, 6, 0, 0, 0, 2, 0, 0, 0, 0, 3, NULL, 3),
(622, 21, 26, 4, 7, 40.41, 41.29, 48, 38, 2, 4, 0, 0, 0, 1, 0, 0, 0, 0, 3, NULL, 3),
(623, 24, 50, 5, 7, 38.16, 39.45, 40, 47, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-10-31', 3),
(624, 17, 29, 3, 7, 39.86, 42.09, 48, 48, 4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-11-29', 3),
(625, 16, 28, 5, 7, 46.15, 46.20, 40, 86, 5, 8, 0, 0, 3, 0, 0, 0, 0, 0, 3, '2025-11-13', 3),
(626, 18, 23, 7, 3, 39.24, 35.62, 60, 36, 5, 1, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-09-19', 3),
(627, 21, 50, 3, 7, 38.70, 38.78, 25, 55, 3, 4, 0, 0, 0, 0, 0, 0, 0, 0, 3, NULL, 3),
(628, 26, 29, 2, 7, 38.49, 46.46, 32, 50, 1, 8, 0, 0, 0, 0, 0, 0, 0, 1, 3, '2025-11-20', 3),
(629, 24, 28, 1, 7, 40.31, 44.61, 12, 170, 3, 4, 0, 0, 0, 0, 0, 1, 0, 0, 3, '2026-01-09', 3),
(630, 17, 18, 7, 3, 45.40, 43.22, 60, 87, 5, 4, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-11-13', 3),
(631, 16, 23, 7, 2, 42.95, 38.77, 145, 40, 3, 3, 0, 0, 2, 1, 0, 0, 0, 0, 3, NULL, 3),
(632, 21, 29, 2, 7, 40.70, 48.29, 150, 40, 1, 3, 0, 0, 1, 0, 0, 0, 0, 1, 3, '2025-10-14', 3),
(633, 28, 50, 7, 0, 46.97, 37.14, 94, 0, 6, 1, 0, 0, 1, 0, 0, 0, 0, 0, 3, '2025-11-13', 3),
(634, 18, 26, 7, 3, 45.57, 41.09, 40, 58, 3, 2, 0, 0, 0, 0, 0, 0, 0, 1, 3, NULL, 3),
(635, 23, 24, 7, 3, 42.03, 39.66, 57, 10, 7, 4, 0, 0, 1, 1, 0, 0, 0, 0, 3, '2025-11-21', 3),
(636, 16, 17, 7, 5, 45.24, 46.07, 76, 106, 3, 5, 0, 0, 1, 0, 0, 0, 1, 0, 3, '2026-01-09', 3),
(637, 21, 28, 3, 7, 45.51, 48.50, 56, 87, 8, 12, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-11-20', 3),
(638, 18, 29, 5, 7, 40.92, 43.96, 72, 86, 3, 4, 0, 0, 1, 2, 0, 0, 0, 0, 3, '2025-12-04', 3),
(639, 23, 50, 2, 7, 40.23, 42.38, 14, 76, 4, 5, 0, 0, 0, 0, 0, 0, 0, 0, 3, NULL, 3),
(640, 16, 26, 7, 4, 40.05, 39.37, 50, 32, 3, 5, 0, 0, 2, 0, 0, 0, 0, 0, 3, '2026-01-09', 3),
(641, 17, 24, 4, 7, 37.69, 39.04, 8, 56, 4, 3, 0, 0, 0, 1, 0, 0, 0, 0, 3, '2026-01-02', 3),
(642, 18, 21, 6, 7, 41.14, 40.50, 38, 57, 2, 7, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-10-25', 3),
(643, 23, 28, 2, 7, 49.32, 53.27, 40, 96, 5, 9, 0, 0, 1, 0, 0, 0, 1, 0, 3, '2025-10-14', 3),
(644, 16, 29, 6, 7, 42.96, 45.04, 40, 60, 5, 6, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-10-09', 3),
(645, 17, 50, 7, 2, 42.75, 39.75, 84, 32, 3, 4, 0, 0, 0, 0, 0, 0, 1, 0, 3, NULL, 3),
(646, 24, 26, 7, 2, 38.82, 36.84, 84, 40, 2, 5, 0, 0, 0, 0, 0, 0, 0, 0, 3, NULL, 3),
(647, 21, 23, 6, 7, 42.64, 42.95, 60, 60, 4, 7, 0, 0, 1, 0, 0, 0, 0, 0, 3, '2025-10-14', 3),
(648, 16, 18, 1, 7, 37.84, 39.80, 16, 40, 3, 3, 0, 0, 1, 0, 0, 1, 0, 0, 3, '2025-11-27', 3),
(649, 17, 28, 7, 5, 46.42, 44.96, 60, 78, 6, 6, 0, 0, 0, 1, 0, 0, 0, 0, 3, '2025-10-09', 3),
(650, 24, 29, 7, 2, 39.86, 38.66, 60, 24, 4, 2, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-12-30', 3),
(651, 26, 50, 3, 7, 41.88, 42.22, 50, 65, 5, 2, 0, 0, 0, 1, 0, 0, 0, 0, 3, NULL, 3),
(652, 16, 21, 7, 4, 43.81, 43.44, 40, 60, 4, 6, 0, 0, 0, 1, 0, 0, 0, 0, 3, NULL, 3),
(653, 17, 23, 3, 7, 39.15, 39.13, 5, 65, 4, 7, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-11-20', 3),
(654, 18, 24, 3, 7, 41.00, 42.60, 32, 56, 3, 5, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2025-10-17', 3),
(655, 26, 28, 1, 7, 42.16, 52.63, 14, 61, 0, 6, 0, 0, 0, 1, 0, 0, 0, 0, 3, '2025-11-20', 3),
(656, 29, 50, 7, 2, 51.05, 41.30, 56, 57, 5, 2, 0, 0, 3, 0, 0, 0, 0, 0, 3, '2025-11-14', 3),
(657, 30, 37, 3, 7, 36.31, 37.57, 59, 52, 2, 4, 0, 0, 0, 1, 0, 0, 0, 0, 4, '2025-12-19', 3),
(658, 38, 40, 7, 5, 35.00, 33.50, 40, 40, 3, 4, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-10-24', 3),
(659, 25, 27, 7, 3, 36.89, 34.21, 48, 30, 2, 4, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-11-28', 3),
(660, 31, 33, 7, 0, 34.81, 34.94, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2026-01-09', 3),
(661, 32, 49, 2, 7, 36.26, 38.73, 57, 40, 2, 4, 0, 0, 0, 2, 0, 0, 0, 0, 4, '2025-11-05', 3),
(662, 30, 38, 7, 6, 37.77, 37.80, 40, 63, 2, 3, 0, 0, 2, 2, 0, 0, 0, 0, 4, '2025-12-19', 3),
(663, 25, 37, 4, 7, 35.49, 38.31, 38, 32, 2, 4, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-12-19', 3),
(664, 31, 40, 0, 7, 36.36, 42.34, 0, 40, 2, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-12-05', 3),
(665, 27, 32, 7, 2, 33.26, 32.22, 54, 8, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-11-26', 3),
(666, 33, 49, 0, 7, 34.94, 39.71, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2026-01-03', 3),
(667, 25, 30, 3, 7, 35.91, 42.52, 40, 40, 2, 5, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2026-01-03', 3),
(668, 31, 38, 6, 7, 30.60, 30.60, 40, 32, 1, 6, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2026-01-08', 3),
(669, 32, 37, 0, 7, 34.20, 38.88, 0, 40, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2026-01-02', 3),
(670, 40, 49, 2, 7, 35.20, 36.73, 43, 40, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-10-16', 3),
(671, 27, 33, 3, 7, 29.00, 30.95, 10, 58, 2, 4, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-11-14', 3),
(672, 30, 31, 7, 6, 33.49, 32.57, 72, 30, 6, 1, 0, 0, 0, 1, 0, 0, 0, 0, 4, '2026-01-03', 3),
(673, 25, 32, 7, 6, 33.65, 33.25, 40, 52, 4, 0, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-11-28', 3),
(674, 38, 49, 3, 7, 36.00, 37.00, 60, 40, 4, 5, 0, 0, 0, 1, 0, 0, 0, 0, 4, '2025-12-02', 3),
(675, 33, 37, 0, 7, 34.94, 38.72, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2026-01-03', 3),
(676, 27, 40, 3, 7, 26.95, 27.33, 54, 58, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-10-31', 3),
(677, 30, 32, 7, 3, 35.51, 33.21, 116, 28, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-11-13', 3),
(678, 31, 49, 2, 7, 37.92, 41.58, 26, 80, 3, 6, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-12-10', 3),
(679, 25, 33, 7, 0, 36.09, 34.94, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2026-01-03', 3),
(680, 27, 38, 7, 3, 36.40, 34.50, 88, 8, 4, 3, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-11-18', 3),
(681, 37, 40, 4, 7, 37.75, 39.83, 32, 58, 7, 1, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2026-01-02', 3),
(682, 30, 49, 3, 7, 39.08, 40.30, 60, 71, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-11-13', 3),
(683, 32, 33, 0, 7, 33.27, 34.84, 0, 70, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 4, '2025-11-28', 3),
(684, 27, 31, 2, 7, 32.10, 31.84, 16, 64, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-10-31', 3),
(685, 25, 40, 4, 7, 34.26, 34.84, 49, 55, 2, 3, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-11-28', 3),
(686, 37, 38, 7, 4, 36.60, 36.00, 54, 32, 7, 6, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-11-19', 3),
(687, 30, 33, 7, 0, 37.54, 34.94, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2026-01-03', 3),
(688, 27, 49, 2, 7, 37.89, 40.05, 32, 39, 6, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2026-01-02', 3),
(689, 32, 40, 1, 7, 38.38, 40.77, 89, 40, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 4, NULL, 3),
(690, 31, 37, 4, 7, 36.76, 38.75, 58, 32, 2, 5, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2026-01-03', 3),
(691, 25, 38, 7, 6, 38.80, 39.00, 63, 52, 5, 4, 0, 0, 3, 1, 0, 0, 0, 0, 4, '2025-12-19', 3),
(692, 27, 30, 2, 7, 36.21, 41.04, 6, 56, 2, 2, 0, 0, 0, 1, 0, 0, 0, 0, 4, '2025-10-30', 3),
(693, 33, 40, 3, 7, 37.98, 39.02, 20, 40, 4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-11-14', 3),
(694, 37, 49, 7, 5, 40.95, 42.34, 73, 40, 8, 10, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-10-16', 3),
(695, 32, 38, 0, 7, 35.20, 37.38, 0, 40, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-12-19', 3),
(696, 25, 31, 7, 3, 33.33, 33.18, 34, 40, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-10-31', 3),
(697, 30, 40, 2, 7, 34.58, 37.74, 8, 58, 0, 3, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-11-27', 3),
(698, 27, 37, 4, 7, 40.46, 40.98, 74, 32, 8, 6, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-11-19', 3),
(699, 33, 38, 1, 7, 36.00, 39.00, 34, 56, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-11-28', 3),
(700, 25, 49, 7, 3, 40.41, 40.91, 103, 60, 5, 1, 0, 0, 1, 0, 0, 0, 0, 0, 4, '2025-12-30', 3),
(701, 31, 32, 7, 0, 39.26, 35.84, 56, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4, '2025-12-05', 3),
(702, 35, 36, 4, 7, 24.03, 23.10, 18, 81, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-12', 3),
(703, 41, 45, 7, 2, 44.90, 41.44, 40, 16, 4, 1, 0, 0, 2, 0, 0, 0, 0, 0, 5, '2025-11-05', 3),
(704, 43, 44, 7, 4, 25.48, 25.86, 32, 32, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-10-08', 3),
(705, 39, 42, 7, 2, 38.95, 36.02, 80, 47, 4, 1, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-11-07', 3),
(706, 34, 48, 7, 4, 39.04, 36.29, 59, 28, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2026-01-09', 3),
(707, 36, 45, 3, 7, 31.40, 32.40, 28, 30, 4, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, NULL, 3),
(708, 35, 43, 7, 3, 37.19, 34.13, 40, 74, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-10-30', 3),
(709, 41, 42, 6, 7, 41.80, 41.80, 76, 46, 6, 1, 0, 0, 0, 0, 1, 0, 0, 0, 5, '2025-12-20', 3),
(710, 44, 48, 6, 7, 26.73, 26.46, 63, 15, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2026-01-09', 3),
(711, 34, 39, 5, 7, 39.07, 39.30, 26, 71, 4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-05', 3),
(712, 36, 43, 7, 2, 35.38, 32.38, 40, 32, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-09-19', 3),
(713, 42, 45, 7, 0, 43.30, 38.63, 52, 0, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-26', 3),
(714, 35, 48, 1, 7, 37.02, 39.03, 4, 49, 4, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2026-01-02', 3),
(715, 34, 41, 3, 7, 38.29, 42.31, 42, 39, 3, 5, 0, 0, 0, 2, 0, 0, 0, 0, 5, '2025-11-18', 3),
(716, 39, 44, 7, 1, 41.74, 36.09, 107, 12, 3, 4, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-21', 3),
(717, 36, 42, 2, 7, 37.42, 43.69, 32, 39, 1, 2, 0, 0, 0, 1, 0, 0, 0, 0, 5, '2025-11-20', 3),
(718, 43, 48, 7, 6, 33.10, 32.39, 37, 20, 4, 2, 0, 0, 0, 0, 0, 1, 0, 0, 5, '2025-10-10', 3),
(719, 34, 45, 7, 4, 36.81, 35.79, 92, 25, 2, 7, 0, 0, 2, 0, 0, 0, 0, 0, 5, NULL, 3),
(720, 35, 39, 2, 7, 37.13, 39.75, 22, 40, 2, 4, 0, 0, 0, 1, 0, 0, 0, 0, 5, NULL, 3),
(721, 41, 44, 7, 1, 38.33, 36.62, 104, 9, 4, 3, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-10-24', 3),
(722, 36, 48, 4, 7, 31.82, 33.69, 35, 40, 2, 3, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-05', 3),
(723, 34, 42, 7, 4, 34.73, 34.65, 96, 96, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-12-04', 3),
(724, 39, 43, 7, 2, 39.02, 38.30, 47, 40, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, NULL, 3),
(725, 44, 45, 6, 7, 30.70, 29.51, 79, 40, 2, 3, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-11-07', 3),
(726, 35, 41, 2, 7, 32.01, 36.40, 20, 40, 1, 2, 0, 0, 0, 2, 0, 0, 0, 0, 5, '2025-11-29', 3),
(727, 34, 36, 6, 7, 31.07, 29.07, 31, 64, 5, 5, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-26', 3),
(728, 39, 48, 7, 4, 32.91, 31.31, 46, 47, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-10-16', 3),
(729, 42, 44, 7, 0, 36.92, 33.44, 32, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-12-20', 3),
(730, 41, 43, 7, 3, 34.39, 31.97, 40, 30, 4, 2, 0, 0, 2, 0, 0, 0, 0, 0, 5, NULL, 3),
(731, 35, 45, 7, 5, 37.23, 35.32, 64, 94, 2, 5, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-28', 3),
(732, 36, 39, 2, 7, 34.32, 36.63, 10, 101, 2, 3, 0, 0, 0, 1, 0, 0, 0, 0, 5, '2025-10-09', 3),
(733, 34, 44, 7, 5, 31.45, 32.53, 16, 34, 1, 5, 0, 0, 0, 1, 0, 0, 0, 0, 5, '2025-11-21', 3),
(734, 41, 48, 7, 2, 33.62, 30.68, 46, 57, 6, 2, 0, 0, 1, 0, 0, 0, 0, 0, 5, '2025-10-16', 3),
(735, 35, 42, 3, 7, 32.32, 36.09, 15, 83, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-12', 3),
(736, 43, 45, 7, 4, 30.39, 29.58, 68, 22, 3, 4, 0, 0, 0, 0, 0, 0, 0, 0, 5, NULL, 3),
(737, 36, 44, 4, 7, 33.81, 35.89, 36, 36, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-10-24', 3),
(738, 39, 41, 3, 7, 37.04, 38.99, 34, 32, 3, 4, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-10-11', 3),
(739, 34, 35, 4, 7, 33.38, 32.43, 57, 38, 1, 2, 0, 0, 0, 1, 0, 0, 0, 0, 5, '2026-01-09', 3),
(740, 45, 48, 7, 3, 30.99, 29.96, 47, 37, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-12', 3),
(741, 42, 43, 7, 0, 32.67, 26.90, 46, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-27', 3),
(742, 36, 41, 0, 7, 32.47, 38.54, 0, 45, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 5, NULL, 3),
(743, 35, 44, 7, 3, 31.76, 30.67, 40, 10, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-10-30', 3),
(744, 39, 45, 7, 4, 32.15, 30.81, 59, 38, 5, 5, 0, 0, 0, 0, 0, 0, 0, 0, 5, NULL, 3),
(745, 34, 43, 5, 7, 30.59, 30.61, 54, 48, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-11-28', 3),
(746, 42, 48, 7, 2, 40.57, 37.18, 32, 40, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5, '2025-12-02', 3),
(750, 27, 138, 7, 6, 33.21, 33.61, 42, 43, 1, 1, 1, 3, 0, 1, 0, 0, 0, 0, 4, '2026-01-30', 4),
(761, 18, 21, 7, 6, 43.74, 41.81, 60, 58, 7, 2, 0, 2, 1, 0, 0, 0, 0, 0, 3, '2026-01-30', 4),
(762, 149, 152, 4, 5, 21.52, 21.91, 34, 35, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 6, '2026-01-31', 4),
(763, 148, 152, 0, 5, 26.44, 27.59, 0, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, '2026-01-31', 4),
(764, 25, 138, 7, 2, 40.90, 37.90, 40, 20, 2, 3, 1, 1, 2, 1, 0, 0, 0, 0, 4, '2026-02-05', 4),
(765, 25, 44, 7, 0, 38.12, 36.41, 80, 0, 5, 1, 0, 2, 0, 0, 0, 0, 0, 0, 4, '2026-02-05', 4),
(766, 20, 29, 2, 7, 33.65, 36.41, 8, 73, 0, 2, 0, 2, 0, 1, 0, 0, 0, 0, 3, '2026-02-05', 4),
(767, 18, 29, 7, 6, 44.31, 43.23, 95, 40, 2, 2, 0, 1, 0, 0, 0, 0, 0, 0, 3, '2026-02-05', 4),
(768, 12, 23, 7, 5, 41.09, 38.66, 86, 40, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 2, '2026-02-05', 4),
(769, 12, 24, 7, 4, 39.24, 38.41, 50, 37, 0, 4, 2, 0, 0, 0, 0, 0, 0, 0, 2, '2026-02-07', 4),
(770, 44, 138, 7, 4, 36.19, 34.23, 55, 40, 0, 3, 0, 1, 1, 0, 0, 0, 0, 0, 4, '2026-02-07', 4),
(771, 146, 147, 5, 0, 20.93, 19.98, 16, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, '2026-02-07', 4),
(772, 17, 18, 7, 6, 43.70, 42.51, 48, 52, 1, 2, 1, 2, 0, 0, 0, 0, 1, 1, 3, '2026-02-07', 4);
INSERT INTO `zapasy` (`id`, `hrac1_id`, `hrac2_id`, `skore1`, `skore2`, `average_home`, `average_away`, `high_finish_home`, `high_finish_away`, `count_100p_home`, `count_100p_away`, `count_120p_home`, `count_120p_away`, `count_140p_home`, `count_140p_away`, `count_160p_home`, `count_160p_away`, `count_180_home`, `count_180_away`, `liga_id`, `datum`, `rocnik_id`) VALUES
(773, 14, 18, 7, 2, 49.87, 48.11, 80, 32, 5, 4, 0, 0, 0, 0, 0, 0, 0, 0, 3, '2026-02-07', 4);

-- --------------------------------------------------------

--
-- Struktura pro pohled `v_hraci_rocnik`
--
DROP TABLE IF EXISTS `v_hraci_rocnik`;

CREATE ALGORITHM=UNDEFINED DEFINER=`a377108_liga`@`%` SQL SECURITY DEFINER VIEW `v_hraci_rocnik` AS select `hs`.`rocnik_id` AS `rocnik_id`,`h`.`id` AS `hrac_id`,`h`.`jmeno` AS `jmeno`,`hs`.`liga_id` AS `liga_id` from (`hraci_v_sezone` `hs` join `hraci` `h` on(`h`.`id` = `hs`.`hrac_id`));

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `hraci`
--
ALTER TABLE `hraci`
  ADD CONSTRAINT `hraci_ibfk_1` FOREIGN KEY (`liga_id`) REFERENCES `ligy` (`id`);

--
-- Omezení pro tabulku `hraci_v_sezone`
--
ALTER TABLE `hraci_v_sezone`
  ADD CONSTRAINT `fk_hs_hrac_unikat` FOREIGN KEY (`hrac_id`) REFERENCES `hraci_unikatni_jmena` (`libovolne_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hs_liga` FOREIGN KEY (`liga_id`) REFERENCES `ligy` (`id`),
  ADD CONSTRAINT `fk_hs_rocnik` FOREIGN KEY (`rocnik_id`) REFERENCES `rocniky` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `prezidentsky_turnaj`
--
ALTER TABLE `prezidentsky_turnaj`
  ADD CONSTRAINT `fk_pp_rocnik` FOREIGN KEY (`rocnik_id`) REFERENCES `rocniky` (`id`);

--
-- Omezení pro tabulku `prezidentsky_zapas`
--
ALTER TABLE `prezidentsky_zapas`
  ADD CONSTRAINT `fk_pp_h1` FOREIGN KEY (`hrac1_id`) REFERENCES `hraci` (`id`),
  ADD CONSTRAINT `fk_pp_h2` FOREIGN KEY (`hrac2_id`) REFERENCES `hraci` (`id`),
  ADD CONSTRAINT `fk_pp_next` FOREIGN KEY (`next_match_id`) REFERENCES `prezidentsky_zapas` (`id`),
  ADD CONSTRAINT `fk_pp_turnaj` FOREIGN KEY (`turnaj_id`) REFERENCES `prezidentsky_turnaj` (`id`);

--
-- Omezení pro tabulku `turnaj_hraci`
--
ALTER TABLE `turnaj_hraci`
  ADD CONSTRAINT `turnaj_hraci_ibfk_1` FOREIGN KEY (`turnaj_id`) REFERENCES `turnaje` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `turnaj_zapasy`
--
ALTER TABLE `turnaj_zapasy`
  ADD CONSTRAINT `turnaj_zapasy_ibfk_1` FOREIGN KEY (`turnaj_id`) REFERENCES `turnaje` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
