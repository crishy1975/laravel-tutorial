<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master-Migration: Erstellt ALLE Tabellen exakt wie im lokalen Schema
 * 
 * Verwendet Raw SQL für 100% Kompatibilität.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // 1. BASIS-TABELLEN (keine Foreign Keys)
        // ═══════════════════════════════════════════════════════════════

        if (!Schema::hasTable('users')) {
            DB::statement("
                CREATE TABLE `users` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL,
                  `email` varchar(255) NOT NULL,
                  `email_verified_at` timestamp NULL DEFAULT NULL,
                  `password` varchar(255) NOT NULL,
                  `remember_token` varchar(100) DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `users_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            DB::statement("
                CREATE TABLE `password_reset_tokens` (
                  `email` varchar(255) NOT NULL,
                  `token` varchar(255) NOT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('sessions')) {
            DB::statement("
                CREATE TABLE `sessions` (
                  `id` varchar(255) NOT NULL,
                  `user_id` bigint(20) unsigned DEFAULT NULL,
                  `ip_address` varchar(45) DEFAULT NULL,
                  `user_agent` text DEFAULT NULL,
                  `payload` longtext NOT NULL,
                  `last_activity` int(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `sessions_user_id_index` (`user_id`),
                  KEY `sessions_last_activity_index` (`last_activity`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('cache')) {
            DB::statement("
                CREATE TABLE `cache` (
                  `key` varchar(255) NOT NULL,
                  `value` mediumtext NOT NULL,
                  `expiration` int(11) NOT NULL,
                  PRIMARY KEY (`key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('cache_locks')) {
            DB::statement("
                CREATE TABLE `cache_locks` (
                  `key` varchar(255) NOT NULL,
                  `owner` varchar(255) NOT NULL,
                  `expiration` int(11) NOT NULL,
                  PRIMARY KEY (`key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('jobs')) {
            DB::statement("
                CREATE TABLE `jobs` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `queue` varchar(255) NOT NULL,
                  `payload` longtext NOT NULL,
                  `attempts` tinyint(3) unsigned NOT NULL,
                  `reserved_at` int(10) unsigned DEFAULT NULL,
                  `available_at` int(10) unsigned NOT NULL,
                  `created_at` int(10) unsigned NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `jobs_queue_index` (`queue`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('job_batches')) {
            DB::statement("
                CREATE TABLE `job_batches` (
                  `id` varchar(255) NOT NULL,
                  `name` varchar(255) NOT NULL,
                  `total_jobs` int(11) NOT NULL,
                  `pending_jobs` int(11) NOT NULL,
                  `failed_jobs` int(11) NOT NULL,
                  `failed_job_ids` longtext NOT NULL,
                  `options` mediumtext DEFAULT NULL,
                  `cancelled_at` int(11) DEFAULT NULL,
                  `created_at` int(11) NOT NULL,
                  `finished_at` int(11) DEFAULT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('failed_jobs')) {
            DB::statement("
                CREATE TABLE `failed_jobs` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `uuid` varchar(255) NOT NULL,
                  `connection` text NOT NULL,
                  `queue` text NOT NULL,
                  `payload` longtext NOT NULL,
                  `exception` longtext NOT NULL,
                  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ═══════════════════════════════════════════════════════════════
        // 2. STAMMDATEN
        // ═══════════════════════════════════════════════════════════════

        if (!Schema::hasTable('adressen')) {
            DB::statement("
                CREATE TABLE `adressen` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `legacy_id` bigint(20) unsigned DEFAULT NULL,
                  `legacy_mid` bigint(20) unsigned DEFAULT NULL,
                  `name` varchar(200) NOT NULL,
                  `strasse` varchar(255) DEFAULT NULL,
                  `hausnummer` varchar(100) DEFAULT NULL,
                  `plz` varchar(10) DEFAULT NULL,
                  `wohnort` varchar(100) DEFAULT NULL,
                  `provinz` varchar(4) DEFAULT NULL,
                  `land` varchar(50) DEFAULT NULL,
                  `telefon` varchar(50) DEFAULT NULL,
                  `handy` varchar(50) DEFAULT NULL,
                  `email` varchar(255) DEFAULT NULL,
                  `email_zweit` varchar(255) DEFAULT NULL,
                  `pec` varchar(255) DEFAULT NULL,
                  `steuernummer` varchar(50) DEFAULT NULL,
                  `mwst_nummer` varchar(50) DEFAULT NULL,
                  `codice_univoco` varchar(20) DEFAULT NULL,
                  `bemerkung` text DEFAULT NULL,
                  `veraendert` tinyint(1) NOT NULL DEFAULT 0,
                  `veraendert_wann` timestamp NULL DEFAULT NULL,
                  `deleted_at` timestamp NULL DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_adressen_legacy_mid` (`legacy_mid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('tour')) {
            DB::statement("
                CREATE TABLE `tour` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `reihenfolge` int(10) unsigned NOT NULL DEFAULT 0,
                  `name` varchar(100) NOT NULL,
                  `beschreibung` text DEFAULT NULL,
                  `aktiv` tinyint(1) DEFAULT 1,
                  `deleted_at` timestamp NULL DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `tour_name_unique` (`name`),
                  KEY `tour_reihenfolge_index` (`reihenfolge`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('fattura_profile')) {
            DB::statement("
                CREATE TABLE `fattura_profile` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `bezeichnung` varchar(100) NOT NULL,
                  `bemerkung` text DEFAULT NULL,
                  `split_payment` tinyint(1) NOT NULL DEFAULT 0,
                  `reverse_charge` tinyint(1) NOT NULL DEFAULT 0,
                  `ritenuta` tinyint(1) NOT NULL DEFAULT 0,
                  `mwst_satz` decimal(5,2) NOT NULL DEFAULT 22.00,
                  `code` varchar(30) DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  `deleted_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `fattura_profile_code_unique` (`code`),
                  KEY `fattura_profile_split_payment_ritenuta_index` (`split_payment`,`ritenuta`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('preis_aufschlaege')) {
            DB::statement("
                CREATE TABLE `preis_aufschlaege` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `jahr` year(4) NOT NULL,
                  `prozent` decimal(5,2) NOT NULL DEFAULT 0.00,
                  `beschreibung` varchar(500) DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `preis_aufschlaege_jahr_index` (`jahr`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ═══════════════════════════════════════════════════════════════
        // 3. GEBÄUDE
        // ═══════════════════════════════════════════════════════════════

        if (!Schema::hasTable('gebaeude')) {
            DB::statement("
                CREATE TABLE `gebaeude` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `legacy_id` bigint(20) unsigned DEFAULT NULL,
                  `legacy_mid` bigint(20) unsigned DEFAULT NULL,
                  `paoloweb_id` int(10) unsigned DEFAULT NULL,
                  `codex` varchar(15) DEFAULT NULL,
                  `postadresse_id` bigint(20) unsigned DEFAULT NULL,
                  `rechnungsempfaenger_id` bigint(20) unsigned DEFAULT NULL,
                  `gebaeude_name` varchar(100) DEFAULT NULL,
                  `strasse` varchar(255) DEFAULT NULL,
                  `hausnummer` varchar(100) DEFAULT NULL,
                  `plz` varchar(50) DEFAULT NULL,
                  `wohnort` varchar(100) DEFAULT NULL,
                  `telefon` varchar(50) DEFAULT NULL,
                  `handy` varchar(50) DEFAULT NULL,
                  `email` varchar(255) DEFAULT NULL,
                  `land` varchar(50) DEFAULT NULL,
                  `bemerkung` text DEFAULT NULL,
                  `bemerkung_buchhaltung` text DEFAULT NULL,
                  `cup` varchar(20) DEFAULT NULL,
                  `cig` varchar(10) DEFAULT NULL,
                  `codice_commessa` varchar(100) DEFAULT NULL,
                  `auftrag_id` varchar(50) DEFAULT NULL,
                  `auftrag_datum` date DEFAULT NULL,
                  `fattura_profile_id` bigint(20) unsigned DEFAULT NULL,
                  `bank_match_text_template` text DEFAULT NULL,
                  `veraendert` tinyint(1) NOT NULL DEFAULT 0,
                  `veraendert_wann` timestamp NULL DEFAULT NULL,
                  `letzter_termin` date DEFAULT NULL,
                  `datum_faelligkeit` date DEFAULT NULL,
                  `geplante_reinigungen` int(11) NOT NULL DEFAULT 1,
                  `gemachte_reinigungen` int(11) NOT NULL DEFAULT 1,
                  `faellig` tinyint(1) NOT NULL DEFAULT 0,
                  `rechnung_schreiben` tinyint(1) NOT NULL DEFAULT 0,
                  `m01` tinyint(1) NOT NULL DEFAULT 0,
                  `m02` tinyint(1) NOT NULL DEFAULT 0,
                  `m03` tinyint(1) NOT NULL DEFAULT 0,
                  `m04` tinyint(1) NOT NULL DEFAULT 0,
                  `m05` tinyint(1) NOT NULL DEFAULT 0,
                  `m06` tinyint(1) NOT NULL DEFAULT 0,
                  `m07` tinyint(1) NOT NULL DEFAULT 0,
                  `m08` tinyint(1) NOT NULL DEFAULT 0,
                  `m09` tinyint(1) NOT NULL DEFAULT 0,
                  `m10` tinyint(1) NOT NULL DEFAULT 0,
                  `m11` tinyint(1) NOT NULL DEFAULT 0,
                  `m12` tinyint(1) NOT NULL DEFAULT 0,
                  `select_tour` bigint(20) unsigned DEFAULT NULL,
                  `deleted_at` timestamp NULL DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `gebaeude_postadresse_id_foreign` (`postadresse_id`),
                  KEY `gebaeude_rechnungsempfaenger_id_foreign` (`rechnungsempfaenger_id`),
                  KEY `gebaeude_auftrag_id_index` (`auftrag_id`),
                  KEY `gebaeude_fattura_profile_id_index` (`fattura_profile_id`),
                  KEY `idx_gebaeude_legacy_mid` (`legacy_mid`),
                  KEY `gebaeude_paoloweb_id_index` (`paoloweb_id`),
                  CONSTRAINT `gebaeude_postadresse_id_foreign` FOREIGN KEY (`postadresse_id`) REFERENCES `adressen` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `gebaeude_rechnungsempfaenger_id_foreign` FOREIGN KEY (`rechnungsempfaenger_id`) REFERENCES `adressen` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('tourgebaeude')) {
            DB::statement("
                CREATE TABLE `tourgebaeude` (
                  `tour_id` bigint(20) unsigned NOT NULL,
                  `gebaeude_id` bigint(20) unsigned NOT NULL,
                  `reihenfolge` int(11) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`tour_id`,`gebaeude_id`),
                  KEY `tourgebaeude_tour_id_reihenfolge_index` (`tour_id`,`reihenfolge`),
                  KEY `tourgebaeude_gebaeude_id_foreign` (`gebaeude_id`),
                  CONSTRAINT `tourgebaeude_gebaeude_id_foreign` FOREIGN KEY (`gebaeude_id`) REFERENCES `gebaeude` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `tourgebaeude_tour_id_foreign` FOREIGN KEY (`tour_id`) REFERENCES `tour` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('gebaeude_aufschlaege')) {
            DB::statement("
                CREATE TABLE `gebaeude_aufschlaege` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `gebaeude_id` bigint(20) unsigned NOT NULL,
                  `prozent` decimal(5,2) NOT NULL DEFAULT 0.00,
                  `grund` varchar(500) DEFAULT NULL,
                  `gueltig_ab` date DEFAULT NULL,
                  `gueltig_bis` date DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `gebaeude_aufschlaege_gebaeude_id_gueltig_ab_gueltig_bis_index` (`gebaeude_id`,`gueltig_ab`,`gueltig_bis`),
                  CONSTRAINT `gebaeude_aufschlaege_gebaeude_id_foreign` FOREIGN KEY (`gebaeude_id`) REFERENCES `gebaeude` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('gebaeude_dokumente')) {
            DB::statement("
                CREATE TABLE `gebaeude_dokumente` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `gebaeude_id` bigint(20) unsigned NOT NULL,
                  `titel` varchar(255) NOT NULL,
                  `beschreibung` text DEFAULT NULL,
                  `kategorie` varchar(100) DEFAULT NULL,
                  `dateiname` varchar(255) NOT NULL,
                  `original_name` varchar(255) NOT NULL,
                  `dateityp` varchar(150) NOT NULL,
                  `dateiendung` varchar(20) NOT NULL,
                  `dateigroesse` bigint(20) unsigned NOT NULL,
                  `pfad` varchar(500) NOT NULL,
                  `dokument_datum` date DEFAULT NULL,
                  `tags` varchar(500) DEFAULT NULL,
                  `ist_wichtig` tinyint(1) NOT NULL DEFAULT 0,
                  `ist_archiviert` tinyint(1) NOT NULL DEFAULT 0,
                  `hochgeladen_von` bigint(20) unsigned DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `gebaeude_dokumente_hochgeladen_von_foreign` (`hochgeladen_von`),
                  KEY `gebaeude_dokumente_kategorie_index` (`kategorie`),
                  KEY `gebaeude_dokumente_dateityp_index` (`dateityp`),
                  KEY `gebaeude_dokumente_dokument_datum_index` (`dokument_datum`),
                  KEY `gebaeude_dokumente_ist_wichtig_index` (`ist_wichtig`),
                  KEY `gebaeude_dokumente_ist_archiviert_index` (`ist_archiviert`),
                  KEY `gebaeude_dokumente_gebaeude_id_kategorie_index` (`gebaeude_id`,`kategorie`),
                  CONSTRAINT `gebaeude_dokumente_gebaeude_id_foreign` FOREIGN KEY (`gebaeude_id`) REFERENCES `gebaeude` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `gebaeude_dokumente_hochgeladen_von_foreign` FOREIGN KEY (`hochgeladen_von`) REFERENCES `users` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('gebaeude_logs')) {
            DB::statement("
                CREATE TABLE `gebaeude_logs` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `gebaeude_id` bigint(20) unsigned NOT NULL,
                  `typ` varchar(50) NOT NULL,
                  `titel` varchar(255) NOT NULL,
                  `beschreibung` text DEFAULT NULL,
                  `user_id` bigint(20) unsigned DEFAULT NULL,
                  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
                  `dokument_pfad` varchar(500) DEFAULT NULL,
                  `referenz_id` bigint(20) unsigned DEFAULT NULL,
                  `referenz_typ` varchar(50) DEFAULT NULL,
                  `kontakt_person` varchar(100) DEFAULT NULL,
                  `kontakt_telefon` varchar(50) DEFAULT NULL,
                  `kontakt_email` varchar(100) DEFAULT NULL,
                  `prioritaet` enum('niedrig','normal','hoch','kritisch') NOT NULL DEFAULT 'normal',
                  `ist_oeffentlich` tinyint(1) NOT NULL DEFAULT 0,
                  `erinnerung_datum` date DEFAULT NULL,
                  `erinnerung_erledigt` tinyint(1) NOT NULL DEFAULT 0,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `gebaeude_logs_user_id_foreign` (`user_id`),
                  KEY `gebaeude_logs_gebaeude_id_created_at_index` (`gebaeude_id`,`created_at`),
                  KEY `gebaeude_logs_gebaeude_id_typ_index` (`gebaeude_id`,`typ`),
                  KEY `gebaeude_logs_typ_index` (`typ`),
                  KEY `gebaeude_logs_erinnerung_datum_index` (`erinnerung_datum`),
                  KEY `gebaeude_logs_prioritaet_index` (`prioritaet`),
                  CONSTRAINT `gebaeude_logs_gebaeude_id_foreign` FOREIGN KEY (`gebaeude_id`) REFERENCES `gebaeude` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `gebaeude_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('artikel_gebaeude')) {
            DB::statement("
                CREATE TABLE `artikel_gebaeude` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `legacy_id` bigint(20) unsigned DEFAULT NULL,
                  `legacy_mid` bigint(20) unsigned DEFAULT NULL,
                  `gebaeude_id` bigint(20) unsigned NOT NULL,
                  `beschreibung` varchar(255) NOT NULL,
                  `anzahl` decimal(10,2) NOT NULL DEFAULT 1.00,
                  `einzelpreis` decimal(10,2) NOT NULL DEFAULT 0.00,
                  `basis_jahr` year(4) NOT NULL,
                  `basis_preis` decimal(12,2) NOT NULL,
                  `aktiv` tinyint(1) NOT NULL DEFAULT 1,
                  `reihenfolge` int(10) unsigned DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `artikel_gebaeude_gebaeude_id_aktiv_index` (`gebaeude_id`,`aktiv`),
                  KEY `artikel_gebaeude_gebaeude_id_reihenfolge_index` (`gebaeude_id`,`reihenfolge`),
                  KEY `idx_artikel_gebaeude_legacy_mid` (`legacy_mid`),
                  CONSTRAINT `artikel_gebaeude_gebaeude_id_foreign` FOREIGN KEY (`gebaeude_id`) REFERENCES `gebaeude` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('timeline')) {
            DB::statement("
                CREATE TABLE `timeline` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `gebaeude_id` int(10) unsigned NOT NULL,
                  `datum` date NOT NULL,
                  `verrechnen` tinyint(1) NOT NULL DEFAULT 1,
                  `verrechnet_am` date DEFAULT NULL,
                  `verrechnet_mit_rn_nummer` varchar(20) DEFAULT NULL,
                  `bemerkung` text NOT NULL,
                  `person_name` varchar(150) NOT NULL,
                  `person_id` int(10) unsigned NOT NULL,
                  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
                  `updated_at` timestamp NULL DEFAULT NULL,
                  `deleted_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `timeline_gebaeude_id_index` (`gebaeude_id`),
                  KEY `timeline_datum_index` (`datum`),
                  KEY `timeline_person_id_index` (`person_id`),
                  KEY `timeline_verrechnen_index` (`verrechnen`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ═══════════════════════════════════════════════════════════════
        // 4. RECHNUNGEN
        // ═══════════════════════════════════════════════════════════════

        if (!Schema::hasTable('rechnungen')) {
            DB::statement("
                CREATE TABLE `rechnungen` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `legacy_id` bigint(20) unsigned DEFAULT NULL,
                  `legacy_progressivo` bigint(20) unsigned DEFAULT NULL,
                  `jahr` year(4) NOT NULL,
                  `laufnummer` int(10) unsigned NOT NULL,
                  `gebaeude_id` bigint(20) unsigned DEFAULT NULL,
                  `rechnungsempfaenger_id` bigint(20) unsigned DEFAULT NULL,
                  `postadresse_id` bigint(20) unsigned DEFAULT NULL,
                  `fattura_profile_id` bigint(20) unsigned DEFAULT NULL,
                  `re_name` varchar(255) DEFAULT NULL,
                  `re_strasse` varchar(255) DEFAULT NULL,
                  `re_hausnummer` varchar(100) DEFAULT NULL,
                  `re_plz` varchar(20) DEFAULT NULL,
                  `re_wohnort` varchar(255) DEFAULT NULL,
                  `re_provinz` varchar(10) DEFAULT NULL,
                  `re_land` varchar(10) DEFAULT NULL,
                  `re_steuernummer` varchar(50) DEFAULT NULL,
                  `re_mwst_nummer` varchar(50) DEFAULT NULL,
                  `re_codice_univoco` varchar(20) DEFAULT NULL,
                  `re_pec` varchar(255) DEFAULT NULL,
                  `post_name` varchar(255) DEFAULT NULL,
                  `post_strasse` varchar(255) DEFAULT NULL,
                  `post_hausnummer` varchar(100) DEFAULT NULL,
                  `post_plz` varchar(20) DEFAULT NULL,
                  `post_wohnort` varchar(255) DEFAULT NULL,
                  `post_provinz` varchar(10) DEFAULT NULL,
                  `post_land` varchar(10) DEFAULT NULL,
                  `post_email` varchar(255) DEFAULT NULL,
                  `post_pec` varchar(255) DEFAULT NULL,
                  `geb_codex` varchar(50) DEFAULT NULL,
                  `geb_name` varchar(255) DEFAULT NULL,
                  `geb_adresse` varchar(500) DEFAULT NULL,
                  `rechnungsdatum` date NOT NULL,
                  `leistungsdaten` varchar(255) DEFAULT NULL,
                  `fattura_causale` text DEFAULT NULL,
                  `zahlungsziel` date DEFAULT NULL,
                  `bezahlt_am` date DEFAULT NULL,
                  `netto_summe` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `mwst_betrag` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `brutto_summe` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `ritenuta_betrag` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `zahlbar_betrag` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `status` enum('draft','sent','paid','cancelled','overdue') NOT NULL DEFAULT 'draft',
                  `typ_rechnung` enum('rechnung','gutschrift') NOT NULL DEFAULT 'rechnung',
                  `profile_bezeichnung` varchar(100) DEFAULT NULL,
                  `mwst_satz` decimal(5,2) NOT NULL DEFAULT 22.00,
                  `split_payment` tinyint(1) NOT NULL DEFAULT 0,
                  `reverse_charge` tinyint(1) NOT NULL DEFAULT 0,
                  `ritenuta` tinyint(1) NOT NULL DEFAULT 0,
                  `ritenuta_prozent` decimal(5,2) DEFAULT NULL,
                  `aufschlag_prozent` decimal(5,2) DEFAULT NULL,
                  `aufschlag_typ` enum('global','individuell','keiner') DEFAULT NULL,
                  `cup` varchar(50) DEFAULT NULL,
                  `cig` varchar(50) DEFAULT NULL,
                  `codice_commessa` varchar(100) DEFAULT NULL,
                  `auftrag_id` varchar(100) DEFAULT NULL,
                  `auftrag_datum` date DEFAULT NULL,
                  `bemerkung` text DEFAULT NULL,
                  `angebot_referenz` varchar(20) DEFAULT NULL,
                  `bemerkung_kunde` text DEFAULT NULL,
                  `zahlungsbedingungen` enum('sofort','netto_7','netto_14','netto_30','netto_60','netto_90','netto_120','bezahlt') DEFAULT 'netto_30',
                  `pdf_pfad` varchar(500) DEFAULT NULL,
                  `xml_pfad` varchar(500) DEFAULT NULL,
                  `externe_referenz` varchar(100) DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  `deleted_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `rechnungen_jahr_laufnummer_unique` (`jahr`,`laufnummer`),
                  KEY `rechnungen_rechnungsempfaenger_id_foreign` (`rechnungsempfaenger_id`),
                  KEY `rechnungen_postadresse_id_foreign` (`postadresse_id`),
                  KEY `rechnungen_fattura_profile_id_foreign` (`fattura_profile_id`),
                  KEY `rechnungen_jahr_laufnummer_index` (`jahr`,`laufnummer`),
                  KEY `rechnungen_status_zahlungsziel_index` (`status`,`zahlungsziel`),
                  KEY `rechnungen_rechnungsdatum_index` (`rechnungsdatum`),
                  KEY `rechnungen_gebaeude_id_status_index` (`gebaeude_id`,`status`),
                  KEY `rechnungen_status_index` (`status`),
                  KEY `idx_rechnungen_legacy_id` (`legacy_id`),
                  CONSTRAINT `rechnungen_fattura_profile_id_foreign` FOREIGN KEY (`fattura_profile_id`) REFERENCES `fattura_profile` (`id`) ON DELETE SET NULL,
                  CONSTRAINT `rechnungen_gebaeude_id_foreign` FOREIGN KEY (`gebaeude_id`) REFERENCES `gebaeude` (`id`) ON DELETE SET NULL,
                  CONSTRAINT `rechnungen_postadresse_id_foreign` FOREIGN KEY (`postadresse_id`) REFERENCES `adressen` (`id`) ON DELETE SET NULL,
                  CONSTRAINT `rechnungen_rechnungsempfaenger_id_foreign` FOREIGN KEY (`rechnungsempfaenger_id`) REFERENCES `adressen` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('rechnung_positionen')) {
            DB::statement("
                CREATE TABLE `rechnung_positionen` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `legacy_id` bigint(20) unsigned DEFAULT NULL,
                  `legacy_artikel_id` bigint(20) unsigned DEFAULT NULL,
                  `rechnung_id` bigint(20) unsigned NOT NULL,
                  `artikel_gebaeude_id` bigint(20) unsigned DEFAULT NULL,
                  `position` int(10) unsigned NOT NULL DEFAULT 0,
                  `beschreibung` varchar(500) NOT NULL,
                  `anzahl` decimal(10,2) NOT NULL DEFAULT 1.00,
                  `einheit` varchar(20) NOT NULL DEFAULT 'Stk',
                  `einzelpreis` decimal(12,2) NOT NULL,
                  `mwst_satz` decimal(5,2) NOT NULL DEFAULT 22.00,
                  `netto_gesamt` decimal(12,2) NOT NULL,
                  `mwst_betrag` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `brutto_gesamt` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `rechnung_positionen_artikel_gebaeude_id_foreign` (`artikel_gebaeude_id`),
                  KEY `rechnung_positionen_rechnung_id_position_index` (`rechnung_id`,`position`),
                  KEY `idx_rechnung_positionen_legacy_id` (`legacy_id`),
                  CONSTRAINT `rechnung_positionen_artikel_gebaeude_id_foreign` FOREIGN KEY (`artikel_gebaeude_id`) REFERENCES `artikel_gebaeude` (`id`) ON DELETE SET NULL,
                  CONSTRAINT `rechnung_positionen_rechnung_id_foreign` FOREIGN KEY (`rechnung_id`) REFERENCES `rechnungen` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('rechnung_logs')) {
            DB::statement("
                CREATE TABLE `rechnung_logs` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `rechnung_id` bigint(20) unsigned NOT NULL,
                  `typ` varchar(50) NOT NULL,
                  `titel` varchar(255) NOT NULL,
                  `beschreibung` text DEFAULT NULL,
                  `user_id` bigint(20) unsigned DEFAULT NULL,
                  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
                  `dokument_pfad` varchar(255) DEFAULT NULL,
                  `referenz_id` bigint(20) unsigned DEFAULT NULL,
                  `referenz_typ` varchar(100) DEFAULT NULL,
                  `kontakt_person` varchar(255) DEFAULT NULL,
                  `kontakt_telefon` varchar(255) DEFAULT NULL,
                  `kontakt_email` varchar(255) DEFAULT NULL,
                  `prioritaet` enum('niedrig','normal','hoch','kritisch') NOT NULL DEFAULT 'normal',
                  `ist_oeffentlich` tinyint(1) NOT NULL DEFAULT 0,
                  `erinnerung_datum` date DEFAULT NULL,
                  `erinnerung_erledigt` tinyint(1) NOT NULL DEFAULT 0,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `rechnung_logs_user_id_foreign` (`user_id`),
                  KEY `rechnung_logs_rechnung_id_typ_index` (`rechnung_id`,`typ`),
                  KEY `rechnung_logs_rechnung_id_created_at_index` (`rechnung_id`,`created_at`),
                  KEY `rechnung_logs_erinnerung_datum_index` (`erinnerung_datum`),
                  KEY `rechnung_logs_typ_index` (`typ`),
                  CONSTRAINT `rechnung_logs_rechnung_id_foreign` FOREIGN KEY (`rechnung_id`) REFERENCES `rechnungen` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `rechnung_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('fattura_xml_logs')) {
            DB::statement("
                CREATE TABLE `fattura_xml_logs` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `rechnung_id` bigint(20) unsigned NOT NULL,
                  `progressivo_invio` varchar(50) NOT NULL,
                  `formato_trasmissione` varchar(10) NOT NULL DEFAULT 'FPR12',
                  `codice_destinatario` varchar(7) DEFAULT NULL,
                  `pec_destinatario` varchar(255) DEFAULT NULL,
                  `xml_file_path` varchar(255) DEFAULT NULL,
                  `xml_filename` varchar(255) DEFAULT NULL,
                  `xml_file_size` int(10) unsigned DEFAULT NULL,
                  `p7m_file_path` varchar(255) DEFAULT NULL,
                  `p7m_filename` varchar(255) DEFAULT NULL,
                  `xml_content` longtext DEFAULT NULL,
                  `status` varchar(50) NOT NULL DEFAULT 'pending',
                  `status_detail` varchar(100) DEFAULT NULL,
                  `sdi_status_code` varchar(10) DEFAULT NULL,
                  `generated_at` timestamp NULL DEFAULT NULL,
                  `signed_at` timestamp NULL DEFAULT NULL,
                  `sent_at` timestamp NULL DEFAULT NULL,
                  `delivered_at` timestamp NULL DEFAULT NULL,
                  `finalized_at` timestamp NULL DEFAULT NULL,
                  `is_valid` tinyint(1) NOT NULL DEFAULT 0,
                  `validation_errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_errors`)),
                  `error_message` text DEFAULT NULL,
                  `error_details` text DEFAULT NULL,
                  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
                  `sdi_ricevuta` text DEFAULT NULL,
                  `sdi_notifiche` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sdi_notifiche`)),
                  `sdi_last_message` text DEFAULT NULL,
                  `sdi_last_check_at` timestamp NULL DEFAULT NULL,
                  `notizen` text DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `fattura_xml_logs_progressivo_invio_unique` (`progressivo_invio`),
                  KEY `fattura_xml_logs_rechnung_id_index` (`rechnung_id`),
                  KEY `fattura_xml_logs_status_index` (`status`),
                  KEY `fattura_xml_logs_generated_at_index` (`generated_at`),
                  KEY `fattura_xml_logs_sent_at_index` (`sent_at`),
                  KEY `fattura_xml_logs_rechnung_id_status_index` (`rechnung_id`,`status`),
                  CONSTRAINT `fattura_xml_logs_rechnung_id_foreign` FOREIGN KEY (`rechnung_id`) REFERENCES `rechnungen` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ═══════════════════════════════════════════════════════════════
        // 5. ANGEBOTE
        // ═══════════════════════════════════════════════════════════════

        if (!Schema::hasTable('angebote')) {
            DB::statement("
                CREATE TABLE `angebote` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `jahr` int(11) NOT NULL,
                  `laufnummer` int(11) NOT NULL,
                  `gebaeude_id` bigint(20) unsigned DEFAULT NULL,
                  `adresse_id` bigint(20) unsigned DEFAULT NULL,
                  `fattura_profile_id` bigint(20) unsigned DEFAULT NULL,
                  `empfaenger_name` varchar(255) DEFAULT NULL,
                  `empfaenger_strasse` varchar(255) DEFAULT NULL,
                  `empfaenger_hausnummer` varchar(255) DEFAULT NULL,
                  `empfaenger_plz` varchar(255) DEFAULT NULL,
                  `empfaenger_ort` varchar(255) DEFAULT NULL,
                  `empfaenger_land` varchar(255) DEFAULT NULL,
                  `empfaenger_email` varchar(255) DEFAULT NULL,
                  `empfaenger_steuernummer` varchar(255) DEFAULT NULL,
                  `empfaenger_codice_fiscale` varchar(255) DEFAULT NULL,
                  `empfaenger_pec` varchar(255) DEFAULT NULL,
                  `empfaenger_codice_destinatario` varchar(7) DEFAULT NULL,
                  `geb_codex` varchar(255) DEFAULT NULL,
                  `geb_name` varchar(255) DEFAULT NULL,
                  `geb_strasse` varchar(255) DEFAULT NULL,
                  `geb_plz` varchar(255) DEFAULT NULL,
                  `geb_ort` varchar(255) DEFAULT NULL,
                  `titel` varchar(255) DEFAULT NULL,
                  `datum` date NOT NULL,
                  `gueltig_bis` date DEFAULT NULL,
                  `netto_summe` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `mwst_satz` decimal(5,2) NOT NULL DEFAULT 22.00,
                  `mwst_betrag` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `brutto_summe` decimal(12,2) NOT NULL DEFAULT 0.00,
                  `einleitung` text DEFAULT NULL,
                  `bemerkung_kunde` text DEFAULT NULL,
                  `bemerkung_intern` text DEFAULT NULL,
                  `status` enum('entwurf','versendet','angenommen','abgelehnt','abgelaufen','rechnung') NOT NULL DEFAULT 'entwurf',
                  `versendet_am` timestamp NULL DEFAULT NULL,
                  `versendet_an_email` varchar(255) DEFAULT NULL,
                  `rechnung_id` bigint(20) unsigned DEFAULT NULL,
                  `umgewandelt_am` timestamp NULL DEFAULT NULL,
                  `pdf_pfad` varchar(255) DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  `deleted_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `angebote_jahr_laufnummer_unique` (`jahr`,`laufnummer`),
                  KEY `angebote_adresse_id_foreign` (`adresse_id`),
                  KEY `angebote_fattura_profile_id_foreign` (`fattura_profile_id`),
                  KEY `angebote_rechnung_id_foreign` (`rechnung_id`),
                  KEY `angebote_status_index` (`status`),
                  KEY `angebote_datum_index` (`datum`),
                  KEY `angebote_gebaeude_id_index` (`gebaeude_id`),
                  CONSTRAINT `angebote_adresse_id_foreign` FOREIGN KEY (`adresse_id`) REFERENCES `adressen` (`id`) ON DELETE SET NULL,
                  CONSTRAINT `angebote_fattura_profile_id_foreign` FOREIGN KEY (`fattura_profile_id`) REFERENCES `fattura_profile` (`id`) ON DELETE SET NULL,
                  CONSTRAINT `angebote_gebaeude_id_foreign` FOREIGN KEY (`gebaeude_id`) REFERENCES `gebaeude` (`id`) ON DELETE SET NULL,
                  CONSTRAINT `angebote_rechnung_id_foreign` FOREIGN KEY (`rechnung_id`) REFERENCES `rechnungen` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('angebot_positionen')) {
            DB::statement("
                CREATE TABLE `angebot_positionen` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `angebot_id` bigint(20) unsigned NOT NULL,
                  `artikel_gebaeude_id` bigint(20) unsigned DEFAULT NULL,
                  `position` int(11) NOT NULL DEFAULT 0,
                  `beschreibung` varchar(500) NOT NULL,
                  `anzahl` decimal(10,2) NOT NULL DEFAULT 1.00,
                  `einheit` varchar(50) NOT NULL DEFAULT 'Stueck',
                  `einzelpreis` decimal(12,2) NOT NULL,
                  `gesamtpreis` decimal(12,2) NOT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `angebot_positionen_artikel_gebaeude_id_foreign` (`artikel_gebaeude_id`),
                  KEY `angebot_positionen_angebot_id_index` (`angebot_id`),
                  CONSTRAINT `angebot_positionen_angebot_id_foreign` FOREIGN KEY (`angebot_id`) REFERENCES `angebote` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `angebot_positionen_artikel_gebaeude_id_foreign` FOREIGN KEY (`artikel_gebaeude_id`) REFERENCES `artikel_gebaeude` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('angebot_logs')) {
            DB::statement("
                CREATE TABLE `angebot_logs` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `angebot_id` bigint(20) unsigned NOT NULL,
                  `user_id` bigint(20) unsigned DEFAULT NULL,
                  `typ` varchar(50) NOT NULL,
                  `titel` varchar(255) NOT NULL,
                  `nachricht` text DEFAULT NULL,
                  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `angebot_logs_user_id_foreign` (`user_id`),
                  KEY `angebot_logs_angebot_id_created_at_index` (`angebot_id`,`created_at`),
                  CONSTRAINT `angebot_logs_angebot_id_foreign` FOREIGN KEY (`angebot_id`) REFERENCES `angebote` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `angebot_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ═══════════════════════════════════════════════════════════════
        // 6. MAHNUNGEN
        // ═══════════════════════════════════════════════════════════════

        if (!Schema::hasTable('mahnung_stufen')) {
            DB::statement("
                CREATE TABLE `mahnung_stufen` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `stufe` int(11) NOT NULL,
                  `name_de` varchar(100) NOT NULL,
                  `name_it` varchar(100) NOT NULL,
                  `tage_ueberfaellig` int(11) NOT NULL,
                  `spesen` decimal(8,2) NOT NULL DEFAULT 0.00,
                  `text_de` text NOT NULL,
                  `text_it` text NOT NULL,
                  `betreff_de` varchar(255) NOT NULL,
                  `betreff_it` varchar(255) NOT NULL,
                  `aktiv` tinyint(1) NOT NULL DEFAULT 1,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `mahnung_stufen_stufe_unique` (`stufe`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('mahnung_einstellungen')) {
            DB::statement("
                CREATE TABLE `mahnung_einstellungen` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `schluessel` varchar(100) NOT NULL,
                  `wert` varchar(255) NOT NULL,
                  `beschreibung` varchar(500) DEFAULT NULL,
                  `typ` varchar(20) NOT NULL DEFAULT 'integer',
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `mahnung_einstellungen_schluessel_unique` (`schluessel`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('mahnungen')) {
            DB::statement("
                CREATE TABLE `mahnungen` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `rechnung_id` bigint(20) unsigned NOT NULL,
                  `mahnung_stufe_id` bigint(20) unsigned NOT NULL,
                  `mahnstufe` int(11) NOT NULL,
                  `mahndatum` date NOT NULL,
                  `tage_ueberfaellig` int(11) NOT NULL,
                  `rechnungsbetrag` decimal(10,2) NOT NULL,
                  `spesen` decimal(8,2) NOT NULL DEFAULT 0.00,
                  `gesamtbetrag` decimal(10,2) NOT NULL,
                  `versandart` enum('email','post','keine') NOT NULL DEFAULT 'keine',
                  `email_gesendet_am` timestamp NULL DEFAULT NULL,
                  `email_adresse` varchar(255) DEFAULT NULL,
                  `email_fehler` tinyint(1) NOT NULL DEFAULT 0,
                  `email_fehler_text` text DEFAULT NULL,
                  `pdf_pfad` varchar(255) DEFAULT NULL,
                  `status` enum('entwurf','gesendet','storniert') NOT NULL DEFAULT 'entwurf',
                  `bemerkung` text DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `mahnungen_rechnung_id_mahnstufe_unique` (`rechnung_id`,`mahnstufe`),
                  KEY `mahnungen_mahnung_stufe_id_foreign` (`mahnung_stufe_id`),
                  CONSTRAINT `mahnungen_mahnung_stufe_id_foreign` FOREIGN KEY (`mahnung_stufe_id`) REFERENCES `mahnung_stufen` (`id`),
                  CONSTRAINT `mahnungen_rechnung_id_foreign` FOREIGN KEY (`rechnung_id`) REFERENCES `rechnungen` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('mahnung_ausschluesse')) {
            DB::statement("
                CREATE TABLE `mahnung_ausschluesse` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `adresse_id` bigint(20) unsigned NOT NULL,
                  `grund` varchar(255) DEFAULT NULL,
                  `bis_datum` date DEFAULT NULL,
                  `aktiv` tinyint(1) NOT NULL DEFAULT 1,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `mahnung_ausschluesse_adresse_id_unique` (`adresse_id`),
                  CONSTRAINT `mahnung_ausschluesse_adresse_id_foreign` FOREIGN KEY (`adresse_id`) REFERENCES `adressen` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('mahnung_rechnung_ausschluesse')) {
            DB::statement("
                CREATE TABLE `mahnung_rechnung_ausschluesse` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `rechnung_id` bigint(20) unsigned NOT NULL,
                  `grund` varchar(255) DEFAULT NULL,
                  `bis_datum` date DEFAULT NULL,
                  `aktiv` tinyint(1) NOT NULL DEFAULT 1,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `mahnung_rechnung_ausschluesse_rechnung_id_unique` (`rechnung_id`),
                  CONSTRAINT `mahnung_rechnung_ausschluesse_rechnung_id_foreign` FOREIGN KEY (`rechnung_id`) REFERENCES `rechnungen` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ═══════════════════════════════════════════════════════════════
        // 7. BANK
        // ═══════════════════════════════════════════════════════════════

        if (!Schema::hasTable('bank_buchungen')) {
            DB::statement("
                CREATE TABLE `bank_buchungen` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `import_datei` varchar(255) DEFAULT NULL,
                  `import_hash` varchar(255) DEFAULT NULL,
                  `import_datum` timestamp NULL DEFAULT NULL,
                  `iban` varchar(34) DEFAULT NULL,
                  `konto_name` varchar(255) DEFAULT NULL,
                  `ntry_ref` varchar(255) DEFAULT NULL,
                  `msg_id` varchar(255) DEFAULT NULL,
                  `betrag` decimal(12,2) NOT NULL,
                  `waehrung` varchar(3) NOT NULL DEFAULT 'EUR',
                  `typ` enum('CRDT','DBIT') NOT NULL,
                  `buchungsdatum` date NOT NULL,
                  `valutadatum` date DEFAULT NULL,
                  `tx_code` varchar(20) DEFAULT NULL,
                  `tx_issuer` varchar(10) DEFAULT NULL,
                  `gegenkonto_name` varchar(255) DEFAULT NULL,
                  `gegenkonto_iban` varchar(34) DEFAULT NULL,
                  `verwendungszweck` text DEFAULT NULL,
                  `rechnung_id` bigint(20) unsigned DEFAULT NULL,
                  `match_status` enum('unmatched','matched','partial','manual','ignored') NOT NULL DEFAULT 'unmatched',
                  `match_info` text DEFAULT NULL,
                  `matched_at` timestamp NULL DEFAULT NULL,
                  `bemerkung` text DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `bank_buchungen_buchungsdatum_index` (`buchungsdatum`),
                  KEY `bank_buchungen_typ_index` (`typ`),
                  KEY `bank_buchungen_match_status_index` (`match_status`),
                  KEY `bank_buchungen_rechnung_id_index` (`rechnung_id`),
                  KEY `bank_buchungen_import_hash_index` (`import_hash`),
                  KEY `bank_buchungen_iban_buchungsdatum_index` (`iban`,`buchungsdatum`),
                  CONSTRAINT `bank_buchungen_rechnung_id_foreign` FOREIGN KEY (`rechnung_id`) REFERENCES `rechnungen` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('bank_import_logs')) {
            DB::statement("
                CREATE TABLE `bank_import_logs` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `dateiname` varchar(255) NOT NULL,
                  `datei_hash` varchar(255) NOT NULL,
                  `anzahl_buchungen` int(11) NOT NULL DEFAULT 0,
                  `anzahl_neu` int(11) NOT NULL DEFAULT 0,
                  `anzahl_duplikate` int(11) NOT NULL DEFAULT 0,
                  `anzahl_matched` int(11) NOT NULL DEFAULT 0,
                  `iban` varchar(34) DEFAULT NULL,
                  `von_datum` date DEFAULT NULL,
                  `bis_datum` date DEFAULT NULL,
                  `saldo_anfang` decimal(12,2) DEFAULT NULL,
                  `saldo_ende` decimal(12,2) DEFAULT NULL,
                  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `bank_import_logs_datei_hash_unique` (`datei_hash`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('bank_matching_config')) {
            DB::statement("
                CREATE TABLE `bank_matching_config` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `score_iban_match` int(11) NOT NULL DEFAULT 100,
                  `score_cig_match` int(11) NOT NULL DEFAULT 80,
                  `score_rechnungsnr_match` int(11) NOT NULL DEFAULT 50,
                  `score_betrag_exakt` int(11) NOT NULL DEFAULT 30,
                  `score_betrag_nah` int(11) NOT NULL DEFAULT 15,
                  `score_betrag_abweichung` int(11) NOT NULL DEFAULT -40,
                  `score_name_token_exact` int(11) NOT NULL DEFAULT 10,
                  `score_name_token_partial` int(11) NOT NULL DEFAULT 5,
                  `auto_match_threshold` int(11) NOT NULL DEFAULT 80,
                  `betrag_abweichung_limit` int(11) NOT NULL DEFAULT 30,
                  `betrag_toleranz_exakt` decimal(8,2) NOT NULL DEFAULT 0.10,
                  `betrag_toleranz_nah` decimal(8,2) NOT NULL DEFAULT 2.00,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // ═══════════════════════════════════════════════════════════════
        // 8. UNTERNEHMENSPROFIL
        // ═══════════════════════════════════════════════════════════════

        if (!Schema::hasTable('unternehmensprofil')) {
            DB::statement("
                CREATE TABLE `unternehmensprofil` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `firmenname` varchar(255) NOT NULL,
                  `firma_zusatz` varchar(255) DEFAULT NULL,
                  `geschaeftsfuehrer` varchar(255) DEFAULT NULL,
                  `handelsregister` varchar(255) DEFAULT NULL,
                  `registergericht` varchar(255) DEFAULT NULL,
                  `strasse` varchar(255) NOT NULL,
                  `hausnummer` varchar(10) NOT NULL,
                  `adresszusatz` varchar(255) DEFAULT NULL,
                  `postleitzahl` varchar(10) NOT NULL,
                  `ort` varchar(255) NOT NULL,
                  `bundesland` varchar(255) DEFAULT NULL,
                  `land` varchar(2) NOT NULL DEFAULT 'IT',
                  `telefon` varchar(30) DEFAULT NULL,
                  `telefon_mobil` varchar(30) DEFAULT NULL,
                  `fax` varchar(30) DEFAULT NULL,
                  `email` varchar(255) NOT NULL,
                  `email_buchhaltung` varchar(255) DEFAULT NULL,
                  `website` varchar(255) DEFAULT NULL,
                  `steuernummer` varchar(255) DEFAULT NULL,
                  `umsatzsteuer_id` varchar(255) DEFAULT NULL,
                  `bank_name` varchar(255) DEFAULT NULL,
                  `iban` varchar(34) DEFAULT NULL,
                  `bic` varchar(11) DEFAULT NULL,
                  `kontoinhaber` varchar(255) DEFAULT NULL,
                  `smtp_host` varchar(255) DEFAULT NULL,
                  `smtp_port` int(11) DEFAULT 587,
                  `smtp_verschluesselung` varchar(10) DEFAULT 'tls',
                  `smtp_benutzername` varchar(255) DEFAULT NULL,
                  `smtp_passwort` varchar(255) DEFAULT NULL,
                  `email_absender` varchar(255) DEFAULT NULL,
                  `email_absender_name` varchar(255) DEFAULT NULL,
                  `email_antwort_an` varchar(255) DEFAULT NULL,
                  `email_cc` varchar(255) DEFAULT NULL,
                  `email_bcc` varchar(255) DEFAULT NULL,
                  `email_signatur` text DEFAULT NULL,
                  `email_fusszeile` text DEFAULT NULL,
                  `pec_smtp_host` varchar(255) DEFAULT NULL,
                  `pec_smtp_port` int(11) DEFAULT 587,
                  `pec_smtp_verschluesselung` varchar(10) DEFAULT 'tls',
                  `pec_smtp_benutzername` varchar(255) DEFAULT NULL,
                  `pec_smtp_passwort` varchar(255) DEFAULT NULL,
                  `pec_email_absender` varchar(255) DEFAULT NULL,
                  `pec_email_absender_name` varchar(255) DEFAULT NULL,
                  `pec_email_antwort_an` varchar(255) DEFAULT NULL,
                  `pec_email_cc` varchar(255) DEFAULT NULL,
                  `pec_email_bcc` varchar(255) DEFAULT NULL,
                  `pec_email_signatur` text DEFAULT NULL,
                  `pec_email_fusszeile` text DEFAULT NULL,
                  `pec_aktiv` tinyint(1) NOT NULL DEFAULT 0,
                  `logo_pfad` varchar(255) DEFAULT NULL,
                  `logo_rechnung_pfad` varchar(255) DEFAULT NULL,
                  `logo_email_pfad` varchar(255) DEFAULT NULL,
                  `logo_breite` int(11) DEFAULT 200,
                  `logo_hoehe` int(11) DEFAULT 80,
                  `briefkopf_text` text DEFAULT NULL,
                  `briefkopf_rechts` text DEFAULT NULL,
                  `fusszeile_text` text DEFAULT NULL,
                  `farbe_primaer` varchar(7) DEFAULT '#003366',
                  `farbe_sekundaer` varchar(7) DEFAULT '#666666',
                  `farbe_akzent` varchar(7) DEFAULT '#0066CC',
                  `schriftart` varchar(50) DEFAULT 'Helvetica',
                  `schriftgroesse` int(11) DEFAULT 10,
                  `rechnungsnummer_praefix` varchar(10) DEFAULT NULL,
                  `rechnungsnummer_startjahr` int(11) DEFAULT 2025,
                  `rechnungsnummer_laenge` int(11) DEFAULT 5,
                  `zahlungsziel_tage` int(11) DEFAULT 30,
                  `zahlungshinweis` text DEFAULT NULL,
                  `kleinunternehmer_hinweis` text DEFAULT NULL,
                  `rechnung_einleitung` text DEFAULT NULL,
                  `rechnung_schlusstext` text DEFAULT NULL,
                  `rechnung_agb_text` text DEFAULT NULL,
                  `ragione_sociale` varchar(255) DEFAULT NULL,
                  `partita_iva` varchar(11) DEFAULT NULL,
                  `codice_fiscale` varchar(16) DEFAULT NULL,
                  `regime_fiscale` varchar(4) DEFAULT 'RF01',
                  `pec_email` varchar(255) DEFAULT NULL,
                  `rea_ufficio` varchar(2) DEFAULT NULL,
                  `rea_numero` varchar(20) DEFAULT NULL,
                  `capitale_sociale` decimal(15,2) DEFAULT NULL,
                  `stato_liquidazione` enum('LN','LS') NOT NULL DEFAULT 'LN',
                  `waehrung` varchar(3) NOT NULL DEFAULT 'EUR',
                  `sprache` varchar(2) NOT NULL DEFAULT 'de',
                  `zeitzone` varchar(50) NOT NULL DEFAULT 'Europe/Rome',
                  `datumsformat` varchar(20) NOT NULL DEFAULT 'd.m.Y',
                  `zahlenformat` varchar(20) NOT NULL DEFAULT 'de_DE',
                  `ist_kleinunternehmer` tinyint(1) NOT NULL DEFAULT 0,
                  `mwst_ausweisen` tinyint(1) NOT NULL DEFAULT 1,
                  `standard_mwst_satz` decimal(5,2) NOT NULL DEFAULT 22.00,
                  `ist_aktiv` tinyint(1) NOT NULL DEFAULT 1,
                  `notizen` text DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT NULL,
                  `updated_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `unternehmensprofil_ist_aktiv_index` (`ist_aktiv`),
                  KEY `unternehmensprofil_firmenname_index` (`firmenname`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        $tables = [
            'angebot_logs', 'angebot_positionen', 'angebote',
            'artikel_gebaeude', 'bank_buchungen', 'bank_import_logs', 'bank_matching_config',
            'cache', 'cache_locks', 'failed_jobs', 'fattura_profile', 'fattura_xml_logs',
            'gebaeude', 'gebaeude_aufschlaege', 'gebaeude_dokumente', 'gebaeude_logs',
            'job_batches', 'jobs', 'mahnung_ausschluesse', 'mahnung_einstellungen',
            'mahnung_rechnung_ausschluesse', 'mahnung_stufen', 'mahnungen',
            'password_reset_tokens', 'preis_aufschlaege', 'rechnung_logs', 'rechnung_positionen',
            'rechnungen', 'sessions', 'timeline', 'tour', 'tourgebaeude',
            'unternehmensprofil', 'users'
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
