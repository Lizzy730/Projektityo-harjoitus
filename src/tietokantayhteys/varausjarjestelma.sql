-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Isäntä: db
-- Luontiaika: 13.11.2026 klo 08:20
-- Palvelimen versio: 8.0.46
-- PHP-versio 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Tietokanta: `varausjarjestelma`
--

-- --------------------------------------------------------

--
-- Rakenne taululle `Luokkahuone`
--

CREATE TABLE `Luokkahuone` (
  `huone_id` int NOT NULL,
  `nimi` varchar(100) NOT NULL,
  `sijainti` varchar(100) DEFAULT NULL,
  `kapasiteetti` int DEFAULT NULL,
  `varustelu` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vedos taulusta `Luokkahuone`
--

INSERT INTO `Luokkahuone` (`huone_id`, `nimi`, `sijainti`, `kapasiteetti`, `varustelu`) VALUES
(1, 'Luokka 1', '1. krs', 20, 'opiskelutila'),
(2, 'Luokka 2', '1. krs', 10, 'tietokonetila'),
(3, 'Luokka 3', '2. krs', 15, 'opiskelutila'),
(4, 'Luokka 4', '2. krs', 25, 'keskustelu/kokoustila'),
(5, 'Luokka 5', '2. krs', 20, 'opiskelutila'),
(6, 'Luokka 6', '3. krs', 20, 'keskustelu/kokoustila'),
(7, 'Luokka 7', '3. krs', 15, 'tietokonetila'),
(8, 'Luokka 8', '3. krs', 30, 'opiskelutila'),
(9, 'Luokka 9', '4. krs', 20, 'opiskelutila'),
(10, 'Luokka 10', '4. krs', 35, 'opiskelutila');

-- --------------------------------------------------------

--
-- Rakenne taululle `Rooli`
--

CREATE TABLE `Rooli` (
  `rooli_id` int NOT NULL,
  `roolin_nimi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Rakenne taululle `User`
--

CREATE TABLE `User` (
  `user_id` int NOT NULL,
  `etunimi` varchar(100) NOT NULL,
  `sukunimi` varchar(100) NOT NULL,
  `sahkoposti` varchar(255) NOT NULL,
  `salasana_hash` varchar(255) NOT NULL,
  `luotu` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vedos taulusta `User`
--

INSERT INTO `User` (`user_id`, `etunimi`, `sukunimi`, `sahkoposti`, `salasana_hash`, `luotu`) VALUES
(1, 'Elli', 'Kortelainen', 'ellikortelainen2007@gmail.com', '$2y$10$UaIfnwY67uRmj1V5eTOHwuYOfm2rk.pL.8eHgf/AqGluguEhh74WK', '2026-11-13 07:31:45');
-- Salasana on hashattu bcrypt-algoritmilla. Salasanan alkuperäinen arvo on "Salasana123".

-- --------------------------------------------------------

--
-- Rakenne taululle `User_Role`
--

CREATE TABLE `User_Role` (
  `user_id` int NOT NULL,
  `rooli_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Rakenne taululle `Varaus`
--

CREATE TABLE `Varaus` (
  `varaus_id` int NOT NULL,
  `user_id` int NOT NULL,
  `huone_id` int NOT NULL,
  `alku_aika` datetime NOT NULL,
  `loppu_aika` datetime NOT NULL,
  `tila` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vedos taulusta `Varaus`
--

INSERT INTO `Varaus` (`varaus_id`, `user_id`, `huone_id`, `alku_aika`, `loppu_aika`, `tila`) VALUES
(7, 1, 5, '2026-11-13 12:00:00', '2026-11-13 13:00:00', 'voimassa');

--
-- Indexes for dumped tables
--

--
-- Indeksit taulukolle `Luokkahuone`
--
ALTER TABLE `Luokkahuone`
  ADD PRIMARY KEY (`huone_id`);

--
-- Indeksit taulukolle `Rooli`
--
ALTER TABLE `Rooli`
  ADD PRIMARY KEY (`rooli_id`);

--
-- Indeksit taulukolle `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `sahkoposti` (`sahkoposti`);

--
-- Indeksit taulukolle `User_Role`
--
ALTER TABLE `User_Role`
  ADD PRIMARY KEY (`user_id`,`rooli_id`),
  ADD KEY `rooli_id` (`rooli_id`);

--
-- Indeksit taulukolle `Varaus`
--
ALTER TABLE `Varaus`
  ADD PRIMARY KEY (`varaus_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `huone_id` (`huone_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Luokkahuone`
--
ALTER TABLE `Luokkahuone`
  MODIFY `huone_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `Rooli`
--
ALTER TABLE `Rooli`
  MODIFY `rooli_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `User`
--
ALTER TABLE `User`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `Varaus`
--
ALTER TABLE `Varaus`
  MODIFY `varaus_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Rajoitteet vedostauluille
--

--
-- Rajoitteet taululle `User_Role`
--
ALTER TABLE `User_Role`
  ADD CONSTRAINT `User_Role_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `User` (`user_id`),
  ADD CONSTRAINT `User_Role_ibfk_2` FOREIGN KEY (`rooli_id`) REFERENCES `Rooli` (`rooli_id`);

--
-- Rajoitteet taululle `Varaus`
--
ALTER TABLE `Varaus`
  ADD CONSTRAINT `Varaus_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `User` (`user_id`),
  ADD CONSTRAINT `Varaus_ibfk_2` FOREIGN KEY (`huone_id`) REFERENCES `Luokkahuone` (`huone_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
