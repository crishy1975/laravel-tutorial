-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 09. Dez 2025 um 18:14
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
-- Tabellenstruktur für Tabelle `unternehmensprofil`
--

CREATE TABLE `unternehmensprofil` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `firmenname` varchar(255) NOT NULL COMMENT 'Offizieller Firmenname',
  `firma_zusatz` varchar(255) DEFAULT NULL COMMENT 'Zusatz (z.B. "GmbH", "SRL")',
  `geschaeftsfuehrer` varchar(255) DEFAULT NULL COMMENT 'Name Geschäftsführer',
  `handelsregister` varchar(255) DEFAULT NULL COMMENT 'Handelsregisternummer',
  `registergericht` varchar(255) DEFAULT NULL COMMENT 'Registergericht',
  `strasse` varchar(255) NOT NULL COMMENT 'Straße',
  `hausnummer` varchar(10) NOT NULL COMMENT 'Hausnummer',
  `adresszusatz` varchar(255) DEFAULT NULL COMMENT 'Adresszusatz (Stockwerk, etc.)',
  `postleitzahl` varchar(10) NOT NULL COMMENT 'PLZ',
  `ort` varchar(255) NOT NULL COMMENT 'Ort/Stadt',
  `bundesland` varchar(255) DEFAULT NULL COMMENT 'Bundesland/Provinz',
  `land` varchar(2) NOT NULL DEFAULT 'IT' COMMENT 'Land (ISO 2-stellig)',
  `telefon` varchar(30) DEFAULT NULL COMMENT 'Telefon',
  `telefon_mobil` varchar(30) DEFAULT NULL COMMENT 'Mobiltelefon',
  `fax` varchar(30) DEFAULT NULL COMMENT 'Fax',
  `email` varchar(255) NOT NULL COMMENT 'Haupt-E-Mail',
  `email_buchhaltung` varchar(255) DEFAULT NULL COMMENT 'E-Mail Buchhaltung',
  `website` varchar(255) DEFAULT NULL COMMENT 'Webseite',
  `steuernummer` varchar(255) DEFAULT NULL COMMENT 'Steuernummer',
  `umsatzsteuer_id` varchar(255) DEFAULT NULL COMMENT 'USt-IdNr. / Partita IVA',
  `bank_name` varchar(255) DEFAULT NULL COMMENT 'Name der Bank',
  `iban` varchar(34) DEFAULT NULL COMMENT 'IBAN',
  `bic` varchar(11) DEFAULT NULL COMMENT 'BIC/SWIFT',
  `kontoinhaber` varchar(255) DEFAULT NULL COMMENT 'Kontoinhaber (falls abweichend)',
  `smtp_host` varchar(255) DEFAULT NULL COMMENT 'SMTP Server',
  `smtp_port` int(11) DEFAULT 587 COMMENT 'SMTP Port',
  `smtp_verschluesselung` varchar(10) DEFAULT 'tls' COMMENT 'TLS/SSL',
  `smtp_benutzername` varchar(255) DEFAULT NULL COMMENT 'SMTP Login',
  `smtp_passwort` varchar(255) DEFAULT NULL COMMENT 'SMTP Passwort (verschlüsselt)',
  `email_absender` varchar(255) DEFAULT NULL COMMENT 'Absender E-Mail',
  `email_absender_name` varchar(255) DEFAULT NULL COMMENT 'Absender Name',
  `email_antwort_an` varchar(255) DEFAULT NULL COMMENT 'Reply-To E-Mail',
  `email_cc` varchar(255) DEFAULT NULL COMMENT 'CC (mehrere durch Komma)',
  `email_bcc` varchar(255) DEFAULT NULL COMMENT 'BCC (mehrere durch Komma)',
  `email_signatur` text DEFAULT NULL COMMENT 'Standard E-Mail Signatur',
  `email_fusszeile` text DEFAULT NULL COMMENT 'Standard E-Mail Fußzeile',
  `pec_smtp_host` varchar(255) DEFAULT NULL COMMENT 'PEC SMTP Server',
  `pec_smtp_port` int(11) DEFAULT 587 COMMENT 'PEC SMTP Port',
  `pec_smtp_verschluesselung` varchar(10) DEFAULT 'tls' COMMENT 'PEC TLS/SSL',
  `pec_smtp_benutzername` varchar(255) DEFAULT NULL COMMENT 'PEC SMTP Login',
  `pec_smtp_passwort` varchar(255) DEFAULT NULL COMMENT 'PEC SMTP Passwort (verschlüsselt)',
  `pec_email_absender` varchar(255) DEFAULT NULL COMMENT 'PEC Absender E-Mail (zertifiziert)',
  `pec_email_absender_name` varchar(255) DEFAULT NULL COMMENT 'PEC Absender Name',
  `pec_email_antwort_an` varchar(255) DEFAULT NULL COMMENT 'PEC Reply-To E-Mail',
  `pec_email_cc` varchar(255) DEFAULT NULL COMMENT 'PEC CC (mehrere durch Komma)',
  `pec_email_bcc` varchar(255) DEFAULT NULL COMMENT 'PEC BCC (mehrere durch Komma)',
  `pec_email_signatur` text DEFAULT NULL COMMENT 'PEC E-Mail Signatur',
  `pec_email_fusszeile` text DEFAULT NULL COMMENT 'PEC E-Mail Fußzeile',
  `pec_aktiv` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'PEC E-Mail-Versand aktiviert',
  `logo_pfad` varchar(255) DEFAULT NULL COMMENT 'Pfad zum Haupt-Logo',
  `logo_rechnung_pfad` varchar(255) DEFAULT NULL COMMENT 'Logo für Rechnungen',
  `logo_email_pfad` varchar(255) DEFAULT NULL COMMENT 'Logo für E-Mails',
  `logo_breite` int(11) DEFAULT 200 COMMENT 'Logo Breite (px)',
  `logo_hoehe` int(11) DEFAULT 80 COMMENT 'Logo Höhe (px)',
  `briefkopf_text` text DEFAULT NULL COMMENT 'Zusätzlicher Text im Briefkopf',
  `briefkopf_rechts` text DEFAULT NULL COMMENT 'Text rechts oben',
  `fusszeile_text` text DEFAULT NULL COMMENT 'Fußzeile auf allen Seiten',
  `farbe_primaer` varchar(7) DEFAULT '#003366' COMMENT 'Primärfarbe (z.B. #003366)',
  `farbe_sekundaer` varchar(7) DEFAULT '#666666' COMMENT 'Sekundärfarbe',
  `farbe_akzent` varchar(7) DEFAULT '#0066CC' COMMENT 'Akzentfarbe',
  `schriftart` varchar(50) DEFAULT 'Helvetica' COMMENT 'Standard-Schriftart',
  `schriftgroesse` int(11) DEFAULT 10 COMMENT 'Standard-Schriftgröße (pt)',
  `rechnungsnummer_praefix` varchar(10) DEFAULT NULL COMMENT 'Präfix (z.B. "RE-")',
  `rechnungsnummer_startjahr` int(11) DEFAULT 2025 COMMENT 'Jahr für Nummerierung',
  `rechnungsnummer_laenge` int(11) DEFAULT 5 COMMENT 'Länge Laufnummer (z.B. 5 = 00001)',
  `zahlungsziel_tage` int(11) DEFAULT 30 COMMENT 'Standard Zahlungsziel (Tage)',
  `zahlungshinweis` text DEFAULT NULL COMMENT 'Text auf Rechnung (z.B. "Bitte unter Angabe...")',
  `kleinunternehmer_hinweis` text DEFAULT NULL COMMENT 'Kleinunternehmer §19 UStG Text',
  `rechnung_einleitung` text DEFAULT NULL COMMENT 'Standard Einleitungstext',
  `rechnung_schlusstext` text DEFAULT NULL COMMENT 'Standard Schlusstext',
  `rechnung_agb_text` text DEFAULT NULL COMMENT 'AGB-Text für Rechnung',
  `ragione_sociale` varchar(255) DEFAULT NULL COMMENT 'IT: Offizielle Firmenbezeichnung',
  `partita_iva` varchar(11) DEFAULT NULL COMMENT 'IT: Partita IVA (11 Ziffern)',
  `codice_fiscale` varchar(16) DEFAULT NULL COMMENT 'IT: Codice Fiscale',
  `regime_fiscale` varchar(4) DEFAULT 'RF01' COMMENT 'IT: RF01-RF19',
  `pec_email` varchar(255) DEFAULT NULL COMMENT 'IT: Zertifizierte PEC E-Mail',
  `rea_ufficio` varchar(2) DEFAULT NULL COMMENT 'IT: REA Büro (z.B. MI, RM)',
  `rea_numero` varchar(20) DEFAULT NULL COMMENT 'IT: REA Nummer',
  `capitale_sociale` decimal(15,2) DEFAULT NULL COMMENT 'IT: Stammkapital',
  `stato_liquidazione` enum('LN','LS') NOT NULL DEFAULT 'LN' COMMENT 'IT: LN=Nicht in Liquidation',
  `waehrung` varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Währung (ISO 3-stellig)',
  `sprache` varchar(2) NOT NULL DEFAULT 'de' COMMENT 'Standard-Sprache (ISO 2-stellig)',
  `zeitzone` varchar(50) NOT NULL DEFAULT 'Europe/Rome' COMMENT 'Zeitzone',
  `datumsformat` varchar(20) NOT NULL DEFAULT 'd.m.Y' COMMENT 'Datumsformat (PHP)',
  `zahlenformat` varchar(20) NOT NULL DEFAULT 'de_DE' COMMENT 'Zahlenformat Locale',
  `ist_kleinunternehmer` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Kleinunternehmer §19 UStG',
  `mwst_ausweisen` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'MwSt auf Dokumenten ausweisen',
  `standard_mwst_satz` decimal(5,2) NOT NULL DEFAULT 22.00 COMMENT 'Standard MwSt-Satz (%)',
  `ist_aktiv` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Aktives Profil',
  `notizen` text DEFAULT NULL COMMENT 'Interne Notizen',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Zentrales Unternehmensprofil mit allen Einstellungen';

--
-- Daten für Tabelle `unternehmensprofil`
--

INSERT INTO `unternehmensprofil` (`id`, `firmenname`, `firma_zusatz`, `geschaeftsfuehrer`, `handelsregister`, `registergericht`, `strasse`, `hausnummer`, `adresszusatz`, `postleitzahl`, `ort`, `bundesland`, `land`, `telefon`, `telefon_mobil`, `fax`, `email`, `email_buchhaltung`, `website`, `steuernummer`, `umsatzsteuer_id`, `bank_name`, `iban`, `bic`, `kontoinhaber`, `smtp_host`, `smtp_port`, `smtp_verschluesselung`, `smtp_benutzername`, `smtp_passwort`, `email_absender`, `email_absender_name`, `email_antwort_an`, `email_cc`, `email_bcc`, `email_signatur`, `email_fusszeile`, `pec_smtp_host`, `pec_smtp_port`, `pec_smtp_verschluesselung`, `pec_smtp_benutzername`, `pec_smtp_passwort`, `pec_email_absender`, `pec_email_absender_name`, `pec_email_antwort_an`, `pec_email_cc`, `pec_email_bcc`, `pec_email_signatur`, `pec_email_fusszeile`, `pec_aktiv`, `logo_pfad`, `logo_rechnung_pfad`, `logo_email_pfad`, `logo_breite`, `logo_hoehe`, `briefkopf_text`, `briefkopf_rechts`, `fusszeile_text`, `farbe_primaer`, `farbe_sekundaer`, `farbe_akzent`, `schriftart`, `schriftgroesse`, `rechnungsnummer_praefix`, `rechnungsnummer_startjahr`, `rechnungsnummer_laenge`, `zahlungsziel_tage`, `zahlungshinweis`, `kleinunternehmer_hinweis`, `rechnung_einleitung`, `rechnung_schlusstext`, `rechnung_agb_text`, `ragione_sociale`, `partita_iva`, `codice_fiscale`, `regime_fiscale`, `pec_email`, `rea_ufficio`, `rea_numero`, `capitale_sociale`, `stato_liquidazione`, `waehrung`, `sprache`, `zeitzone`, `datumsformat`, `zahlenformat`, `ist_kleinunternehmer`, `mwst_ausweisen`, `standard_mwst_satz`, `ist_aktiv`, `notizen`, `created_at`, `updated_at`) VALUES
(1, 'Resch GmbH - Meisterbetrieb', 'GmbH', 'Resch Christian', 'Bozen', NULL, 'Galvanistr.', '6', 'christian@resch.bz', '39100', 'Bozen', 'BZ', 'IT', '+393384693481', '+39 3384693481', NULL, 'christian@resch.bz', 'christian@resch.bz', 'https://resch.bz', '01699660211', '01699660211', 'Raifeisenkasse Unterland', 'IT26R0811458482000302002531', NULL, NULL, 'smtp.flockmail.com', 587, 'tls', 'christian@resch.bz', 'jo4qPW7VUg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'smtps.pec.aruba.it', 587, 'tls', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'logos/logo_haupt_1764349652.jpg', 'logos/logo_rechnung_1764349368.jpg', NULL, 200, 200, 'Meisterbetrieb Resch Gmbh', NULL, 'Ihr Kaminkehrer für Ihre Sicherheit!\r\nIl vostro spazzacamino per la vostra sicurezza!', '#050505', '#666666', '#fbfcfe', 'Helvetica', 10, 'RE-', 2025, 5, 30, NULL, NULL, NULL, NULL, NULL, 'Resch GmbH', '01699660211', '01699660211', 'RF01', 'resch.ohg@gmail.com', 'BZ', '157771', NULL, 'LN', 'EUR', 'de', 'Europe/Rome', 'd.m.Y', 'de_DE', 0, 1, 22.00, 1, NULL, '2025-11-26 04:11:59', '2025-11-29 17:37:36');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `unternehmensprofil`
--
ALTER TABLE `unternehmensprofil`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unternehmensprofil_ist_aktiv_index` (`ist_aktiv`),
  ADD KEY `unternehmensprofil_firmenname_index` (`firmenname`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `unternehmensprofil`
--
ALTER TABLE `unternehmensprofil`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
