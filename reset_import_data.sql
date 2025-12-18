-- ═══════════════════════════════════════════════════════════════════════════
-- RESET IMPORT DATA
-- Löscht alle importierten Daten für einen Neu-Import
-- ═══════════════════════════════════════════════════════════════════════════
-- 
-- ACHTUNG: Dieses Script löscht ALLE Daten unwiderruflich!
-- 
-- Verwendung:
--   mysql -u root -p uschi_web < reset_import_data.sql
--   
-- Oder in phpMyAdmin / MySQL Workbench ausführen
-- ═══════════════════════════════════════════════════════════════════════════

-- Sicherheitsabfrage deaktivieren (für TRUNCATE)
SET FOREIGN_KEY_CHECKS = 0;

-- ═══════════════════════════════════════════════════════════════════════════
-- 1. ABHÄNGIGE TABELLEN ZUERST (Kinder)
-- ═══════════════════════════════════════════════════════════════════════════

-- Bank-Buchungen (referenziert Rechnungen)
TRUNCATE TABLE `bank_buchungen`;
SELECT 'bank_buchungen gelöscht' AS Status;

-- Rechnungspositionen (referenziert Rechnungen, Artikel)
TRUNCATE TABLE `rechnung_positionen`;
SELECT 'rechnung_positionen gelöscht' AS Status;

-- Rechnungen (referenziert Gebäude, Adressen)
TRUNCATE TABLE `rechnungen`;
SELECT 'rechnungen gelöscht' AS Status;

-- Timeline (referenziert Gebäude)
TRUNCATE TABLE `timelines`;
SELECT 'timelines gelöscht' AS Status;

-- Tour-Gebäude Pivot (referenziert Touren, Gebäude)
TRUNCATE TABLE `tourgebaeude`;
SELECT 'tourgebaeude gelöscht' AS Status;

-- Artikel-Gebäude (referenziert Gebäude)
TRUNCATE TABLE `artikel_gebaeude`;
SELECT 'artikel_gebaeude gelöscht' AS Status;

-- Gebäude-Aufschläge (referenziert Gebäude)
TRUNCATE TABLE `gebaeude_aufschlaege`;
SELECT 'gebaeude_aufschlaege gelöscht' AS Status;

-- ═══════════════════════════════════════════════════════════════════════════
-- 2. HAUPT-TABELLEN
-- ═══════════════════════════════════════════════════════════════════════════

-- Gebäude (referenziert Adressen)
TRUNCATE TABLE `gebaeude`;
SELECT 'gebaeude gelöscht' AS Status;

-- Adressen (Basis-Tabelle)
TRUNCATE TABLE `adressen`;
SELECT 'adressen gelöscht' AS Status;

-- ═══════════════════════════════════════════════════════════════════════════
-- 3. OPTIONAL: EINSTELLUNGEN (auskommentieren falls behalten)
-- ═══════════════════════════════════════════════════════════════════════════

-- Preis-Aufschläge (globale Inflation)
-- TRUNCATE TABLE `preis_aufschlaege`;
-- SELECT 'preis_aufschlaege gelöscht' AS Status;

-- Fattura-Profile (Rechnungstypen)
-- TRUNCATE TABLE `fattura_profile`;
-- SELECT 'fattura_profile gelöscht' AS Status;

-- Touren (falls neu importiert werden sollen)
-- TRUNCATE TABLE `touren`;
-- SELECT 'touren gelöscht' AS Status;

-- ═══════════════════════════════════════════════════════════════════════════
-- FOREIGN KEY CHECKS WIEDER AKTIVIEREN
-- ═══════════════════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════════════════════
-- FERTIG
-- ═══════════════════════════════════════════════════════════════════════════

SELECT '✅ Alle Daten gelöscht. Bereit für Neu-Import!' AS Ergebnis;
