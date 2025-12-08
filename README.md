# Access → Laravel Import

Dieses Modul importiert Daten aus der alten Access-Datenbank in das neue Laravel-System.

---

## 📋 Inhaltsverzeichnis

- [Übersicht](#übersicht)
- [Installation](#installation)
- [XML-Export aus Access](#xml-export-aus-access)
- [Import durchführen](#import-durchführen)
- [Befehle im Überblick](#befehle-im-überblick)
- [Feld-Mapping](#feld-mapping)
- [Fehlerbehebung](#fehlerbehebung)
- [Technische Details](#technische-details)

---

## Übersicht

### Datenmodell

```
┌─────────────────────────────────────────────────────────────────────┐
│                          STAMMDATEN                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────────┐                                                   │
│  │   Adresse    │ ←───────────────────┐                             │
│  │   (mId)      │                     │                             │
│  └──────────────┘                     │                             │
│         ↑                             │                             │
│         │ Postadresse                 │ Rechnungsempfaenger         │
│         │ Rechnungsempfaenger         │                             │
│         │                             │                             │
│  ┌──────────────┐              ┌──────────────┐                     │
│  │   Gebaeude   │              │   Artikel    │                     │
│  │   (mId)      │ ←────────────│  (Stamm)     │                     │
│  └──────────────┘   herkunft   └──────────────┘                     │
│         │                                                           │
├─────────│───────────────────────────────────────────────────────────┤
│         │              TRANSAKTIONEN                                │
├─────────│───────────────────────────────────────────────────────────┤
│         │ herkunft                                                  │
│         ↓                                                           │
│  ┌──────────────┐                                                   │
│  │  Rechnung    │ ←────────────┐                                    │
│  │ (FatturaPA)  │              │                                    │
│  └──────────────┘              │ herkunft (idFatturaPA)             │
│                         ┌──────────────┐                            │
│                         │  Rechnungs-  │                            │
│                         │  Positionen  │                            │
│                         └──────────────┘                            │
└─────────────────────────────────────────────────────────────────────┘
```

### Referenz-Schlüssel

| Tabelle | Referenz-Feld | Verweist auf |
|---------|---------------|--------------|
| `Artikel.herkunft` | → | `Gebaeude.mId` |
| `Gebaeude.Postadresse` | → | `Adresse.mId` |
| `Gebaeude.Rechnungsempfaenger` | → | `Adresse.mId` |
| `FatturaPAAbfrage.herkunft` | → | `Gebaeude.mId` |
| `ArtikelFatturaPAAbfrage.herkunft` | → | `FatturaPAAbfrage.idFatturaPA` |

---

## Installation

### 1. Dateien kopieren

```bash
# Migration
cp database/migrations/2025_12_07_000001_fix_columns_for_import.php \
   database/migrations/

# Service
mkdir -p app/Services/Import
cp app/Services/Import/AccessImportService.php \
   app/Services/Import/

# Commands
cp app/Console/Commands/ImportAccessData.php \
   app/Console/Commands/
cp app/Console/Commands/FixGebaeudeNamen.php \
   app/Console/Commands/
```

### 2. Doctrine DBAL installieren

Für `->change()` in Migrationen wird DBAL benötigt:

```bash
composer require doctrine/dbal
```

### 3. Models anpassen

Füge die Legacy-Felder zu `$fillable` hinzu:

**app/Models/Adresse.php:**
```php
protected $fillable = [
    'legacy_id',
    'legacy_mid',
    // ... bestehende Felder
];
```

**app/Models/Gebaeude.php:**
```php
protected $fillable = [
    'legacy_id',
    'legacy_mid',
    // ... bestehende Felder
];
```

**app/Models/ArtikelGebaeude.php:**
```php
protected $fillable = [
    'legacy_id',
    'legacy_mid',
    // ... bestehende Felder
];
```

**app/Models/Rechnung.php:**
```php
protected $fillable = [
    'legacy_id',
    'legacy_progressivo',
    // ... bestehende Felder
];
```

**app/Models/RechnungPosition.php:**
```php
protected $fillable = [
    'legacy_id',
    'legacy_artikel_id',
    // ... bestehende Felder
];
```

### 4. Migration ausführen

```bash
php artisan migrate
```

---

## XML-Export aus Access

### Benötigte Dateien

Exportiere folgende Tabellen/Abfragen aus Access als XML:

| Dateiname | Access-Quelle | Beschreibung |
|-----------|---------------|--------------|
| `Adresse.xml` | Tabelle: Adresse | Alle Adressen |
| `Gebaeude.xml` | Tabelle: Gebaeude | Alle Gebäude |
| `Artikel.xml` | Tabelle: Artikel | Artikel-Stammdaten |
| `FatturaPAXmlAbfrage.xml` | Abfrage: FatturaPAXmlAbfrage | Rechnungen mit JOINs |
| `ArtikelFatturaPAAbfrage.xml` | Abfrage: ArtikelFatturaPAAbfrage | Rechnungspositionen |

### Export-Anleitung

1. Öffne die Access-Datenbank
2. Rechtsklick auf Tabelle/Abfrage → **Exportieren** → **XML-Datei**
3. Wähle **Nur Daten** (kein Schema)
4. Speichere als UTF-8

### Dateien ablegen

Lege alle XML-Dateien in folgendem Ordner ab:

```
storage/import/
├── Adresse.xml
├── Gebaeude.xml
├── Artikel.xml
├── FatturaPAXmlAbfrage.xml
└── ArtikelFatturaPAAbfrage.xml
```

---

## Import durchführen

### Schnellstart (Alles auf einmal)

```bash
# 1. Dry-Run (testen ohne zu speichern)
php artisan import:access --all --dry-run

# 2. Echter Import
php artisan import:access --all

# 3. Fehlende Gebäude-Namen vom Rechnungsempfänger übernehmen
php artisan import:fix-gebaeude
```

### Schrittweiser Import (empfohlen)

```bash
# 1. Adressen
php artisan import:access --adressen

# 2. Gebäude
php artisan import:access --gebaeude

# 3. Gebäude-Namen fixen
php artisan import:fix-gebaeude

# 4. Artikel
php artisan import:access --artikel

# 5. Rechnungen
php artisan import:access --rechnungen

# 6. Rechnungspositionen
php artisan import:access --positionen
```

### Import-Reihenfolge (wichtig!)

Die Reihenfolge ist wegen der Referenzen zwingend einzuhalten:

```
1. Adressen      ← Keine Abhängigkeiten
       ↓
2. Gebäude       ← Braucht: Adressen
       ↓
3. Artikel       ← Braucht: Gebäude
       ↓
4. Rechnungen    ← Braucht: Gebäude + Adressen
       ↓
5. Positionen    ← Braucht: Rechnungen
```

---

## Befehle im Überblick

### `import:access`

Hauptbefehl für den Datenimport.

```bash
php artisan import:access [optionen]
```

| Option | Beschreibung |
|--------|--------------|
| `--all` | Alle Tabellen importieren |
| `--adressen` | Nur Adressen importieren |
| `--gebaeude` | Nur Gebäude importieren |
| `--artikel` | Nur Artikel importieren |
| `--rechnungen` | Nur Rechnungen importieren |
| `--positionen` | Nur Rechnungspositionen importieren |
| `--dry-run` | Testlauf ohne Speichern |
| `--force` | Bestehende Einträge überschreiben |
| `--path=PFAD` | Alternativer XML-Ordner |
| `-v` | Verbose: Alle Fehler anzeigen |

**Beispiele:**

```bash
# Interaktives Menü
php artisan import:access

# Nur Adressen, mit Test
php artisan import:access --adressen --dry-run

# Alles importieren, anderer Pfad
php artisan import:access --all --path=/backup/xml-export

# Verbose-Modus für alle Fehler
php artisan import:access --rechnungen -v
```

### `import:fix-gebaeude`

Füllt fehlende Gebäude-Daten mit Daten vom Rechnungsempfänger.

```bash
php artisan import:fix-gebaeude [optionen]
```

| Option | Beschreibung |
|--------|--------------|
| `--dry-run` | Nur anzeigen, nicht ändern |
| `--force` | Auch Gebäude mit Namen überschreiben |

**Was wird kopiert:**
- `name` → `gebaeude_name`
- `strasse` → `strasse`
- `hausnummer` → `hausnummer`
- `plz` → `plz`
- `wohnort` → `wohnort`
- `land` → `land`

---

## Feld-Mapping

### Adressen

| Access | Laravel | Notizen |
|--------|---------|---------|
| `id` | `legacy_id` | Alte ID |
| `mId` | `legacy_mid` | Referenz-Schlüssel |
| `Vorname` + `Nachname` | `name` | Zusammengefügt |
| `Strasse` | `strasse` | |
| `Nr` | `hausnummer` | |
| `PLZ` | `plz` | |
| `Wohnort` | `wohnort` | |
| `Provinz` | `provinz` | |
| `Land` | `land` | Default: IT |
| `Telefon` | `telefon` | |
| `Handy` | `handy` | |
| `Email` | `email` | |
| `Pec` | `pec` | |
| `Steuernummer` | `steuernummer` | |
| `Mwst` | `mwst_nummer` | |
| `CodiceUnivoco` | `codice_univoco` | |
| `Bemerkung` | `bemerkung` | |

### Gebäude

| Access | Laravel | Notizen |
|--------|---------|---------|
| `id` | `legacy_id` | Alte ID |
| `mId` | `legacy_mid` | Referenz-Schlüssel |
| `Codex` | `codex` | |
| `Namen1` | `gebaeude_name` | |
| `Strasse` | `strasse` | |
| `Hausnummer` | `hausnummer` | |
| `PLZ` | `plz` | |
| `Wohnort` | `wohnort` | |
| `Postadresse` | `postadresse_id` | Via mId aufgelöst |
| `Rechnungsempfaenger` | `rechnungsempfaenger_id` | Via mId aufgelöst |
| `jan`...`dez` | `m01`...`m12` | Monats-Flags |
| `anzReinigung` | `gemachte_reinigungen` | |
| `anzReinigungPlan` | `geplante_reinigungen` | |
| `Faellig` | `faellig` | |
| `LetzterTermin` | `letzter_termin` | |

### Artikel (Stamm)

| Access | Laravel | Notizen |
|--------|---------|---------|
| `id` | `legacy_id` | Alte ID |
| `mId` | `legacy_mid` | Referenz-Schlüssel |
| `herkunft` | `gebaeude_id` | Via Gebaeude.mId aufgelöst |
| `Beschreibung` | `beschreibung` | |
| `Einzelpreis` | `einzelpreis` | |
| `Anzahl` | `anzahl` | |

### Rechnungen

| Access | Laravel | Notizen |
|--------|---------|---------|
| `idFatturaPA` | `legacy_id` | Referenz-Schlüssel |
| `ProgressivoInvio` | `legacy_progressivo` | |
| `Numero` | `laufnummer` | |
| `Data` | `rechnungsdatum` | |
| `DataPagamento` | `zahlungsziel` | |
| `herkunft` | `gebaeude_id` | Via Gebaeude.mId |
| `Rechnungsempfaenger` | `rechnungsempfaenger_id` | Via Adresse.mId |
| `RechnungsBetrag` | `netto_summe` | |
| `MwStr` | `mwst_betrag` | |
| `Betrag` | `brutto_summe` | |
| `Rit` | `ritenuta_betrag` | |
| `Bezahlt` | `status` | 0=sent, 1=paid |
| `Causale` | `fattura_causale` | |
| `CIG` | `cig` | |
| `OrdineId` | `auftrag_id` | |
| `OrdineData` | `auftrag_datum` | |

### Rechnungspositionen

| Access | Laravel | Notizen |
|--------|---------|---------|
| `id` | `legacy_id` | Alte ID |
| `idHerkunftArtikel` | `legacy_artikel_id` | |
| `herkunft` | `rechnung_id` | Via idFatturaPA aufgelöst |
| `Beschreibung` | `beschreibung` | HTML-Entities dekodiert |
| `Einzelpreis` | `einzelpreis` | |
| `Anzahl` | `anzahl` | |
| `MwStSatz` | `mwst_satz` | Default: 22 |

---

## Fehlerbehebung

### Fehlerprotokoll

Alle Fehler werden automatisch in eine Log-Datei geschrieben:

```
storage/logs/import_errors_YYYY-MM-DD_HHMMSS.log
```

### Häufige Fehler

#### "Data too long for column 'hausnummer'"

**Ursache:** Alte Daten haben lange Hausnummern wie "244 a-b-c-d"

**Lösung:** Migration `2025_12_07_000001_fix_columns_for_import.php` ausführen

#### "Column 'rechnungsempfaenger_id' cannot be null"

**Ursache:** Gebäude ohne zugeordneten Rechnungsempfänger

**Lösung:** Migration macht diese Felder nullable

#### "Field 'post_name' doesn't have a default value"

**Ursache:** Snapshot-Felder waren NOT NULL

**Lösung:** Migration macht alle Snapshot-Felder nullable

#### "Gebäude nicht gefunden: herkunft=X"

**Ursache:** Artikel verweist auf gelöschtes/nicht existierendes Gebäude

**Lösung:** Normal - diese Artikel werden übersprungen

### Daten komplett neu importieren

```sql
-- ACHTUNG: Löscht alle importierten Daten!

-- 1. Abhängige Tabellen zuerst
DELETE FROM rechnung_positionen WHERE legacy_id IS NOT NULL;
DELETE FROM rechnungen WHERE legacy_id IS NOT NULL;
DELETE FROM artikel_gebaeude WHERE legacy_id IS NOT NULL;

-- 2. Dann Haupttabellen
DELETE FROM gebaeude WHERE legacy_id IS NOT NULL;
DELETE FROM adressen WHERE legacy_id IS NOT NULL;
```

### Encoding-Probleme (Umlaute)

Falls Umlaute falsch angezeigt werden:

```bash
# XML zu UTF-8 konvertieren (Linux/Mac)
iconv -f ISO-8859-1 -t UTF-8 input.xml > output.xml

# Windows PowerShell
Get-Content input.xml -Encoding Default | Set-Content output.xml -Encoding UTF8
```

---

## Technische Details

### Duplikat-Erkennung

Der Import erkennt bereits importierte Datensätze anhand der `legacy_mid` bzw. `legacy_id` Felder:

- **PHP-Ebene:** Prüfung vor jedem Insert
- **Datenbank-Ebene:** UNIQUE Index verhindert Duplikate

### Transaktionen

Der Import läuft in einer Datenbank-Transaktion:
- Bei Fehlern: Automatischer Rollback
- Bei Erfolg: Commit am Ende

### Performance

| Tabelle | ~1.000 Datensätze | ~10.000 Datensätze |
|---------|-------------------|---------------------|
| Adressen | ~2 Sek. | ~15 Sek. |
| Gebäude | ~3 Sek. | ~20 Sek. |
| Artikel | ~2 Sek. | ~15 Sek. |
| Rechnungen | ~10 Sek. | ~60 Sek. |
| Positionen | ~15 Sek. | ~90 Sek. |

### Dateien

```
app/
├── Console/Commands/
│   ├── ImportAccessData.php      # Haupt-Import-Command
│   └── FixGebaeudeNamen.php      # Post-Import-Fix
│
├── Services/Import/
│   └── AccessImportService.php   # Import-Logik
│
database/migrations/
│   └── 2025_12_07_000001_fix_columns_for_import.php
│
storage/
├── import/                       # XML-Dateien hierher
│   ├── Adresse.xml
│   ├── Gebaeude.xml
│   ├── Artikel.xml
│   ├── FatturaPAXmlAbfrage.xml
│   └── ArtikelFatturaPAAbfrage.xml
│
└── logs/
    └── import_errors_*.log       # Fehlerprotokolle
```

---

## Support

Bei Fragen oder Problemen:
1. Prüfe das Fehlerprotokoll in `storage/logs/`
2. Führe den Import mit `--dry-run` aus
3. Prüfe die XML-Dateien auf korrektes Encoding

---

*Dokumentation erstellt: Dezember 2025*