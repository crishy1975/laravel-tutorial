-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 09. Dez 2025 um 18:17
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `laravel_tutorial`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fattura_profile`
--

CREATE TABLE `fattura_profile` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bezeichnung` varchar(100) NOT NULL,
  `bemerkung` text DEFAULT NULL,
  `split_payment` tinyint(1) NOT NULL DEFAULT 0,
  `reverse_charge` tinyint(1) NOT NULL DEFAULT 0,
  `ritenuta` tinyint(1) NOT NULL DEFAULT 0,
  `mwst_satz` decimal(5,2) NOT NULL DEFAULT 22.00,
  `code` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `fattura_profile`
--

INSERT INTO `fattura_profile` (`id`, `bezeichnung`, `bemerkung`, `split_payment`, `reverse_charge`, `ritenuta`, `mwst_satz`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Firma, Reverse Charge', NULL, 0, -1, 0, 0.00, NULL, NULL, NULL, NULL),
(2, 'Privatkunde', NULL, 0, 0, 0, 10.00, NULL, NULL, NULL, NULL),
(3, 'Öffentlich', NULL, -1, 0, 0, 22.00, NULL, NULL, NULL, NULL),
(4, 'Kondominium', NULL, 0, 0, -1, 10.00, NULL, NULL, NULL, NULL),
(5, 'Kondominium Gewerblich', NULL, 0, 0, -1, 22.00, NULL, NULL, NULL, NULL);

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `fattura_profile`
--
ALTER TABLE `fattura_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fattura_profile_code_unique` (`code`),
  ADD KEY `fattura_profile_split_payment_ritenuta_index` (`split_payment`,`ritenuta`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `fattura_profile`
--
ALTER TABLE `fattura_profile`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
