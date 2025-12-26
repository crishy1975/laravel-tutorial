# 📥 Import aus Access - Komplett-Anleitung

## ✅ Voraussetzungen & Reihenfolge

**WICHTIG:** Die Import-Reihenfolge muss eingehalten werden!

```bash
1. php artisan import:access --adressen      # Zuerst!
2. php artisan import:access --gebaeude      # Dann!
3. php artisan import:timeline               # NEU (Reinigungen 2024+2025)
4. php artisan import:rechnungen             # NEU (FatturaPA)
5. php artisan import:access --positionen    # Zuletzt
```

---

## 📁 Benötigte XML-Dateien

| Datei | Access-Tabelle | Import-Befehl |
|-------|----------------|---------------|
| `Adressen.xml` | Adressen | `import:access --adressen` |
| `GebaeudeAbfrage.xml` | GebaeudeAbfrage | `import:access --gebaeude` |
| `DatumAusfuehrung.xml` | DatumAusfuehrung | `import:timeline` 🆕 |
| `FatturaPA.xml` | FatturaPA | `import:rechnungen` 🆕 |
| `ArtikelGebaeude.xml` | ArtikelGebaeude | `import:access --positionen` |

### Export aus Access

Für jede Tabelle:
```
Rechtsklick auf Tabelle → Exportieren → XML
```

---

## 📅 Timeline-Import (Reinigungen)

### XML-Format (DatumAusfuehrung.xml)

```xml
<dataroot>
  <DatumAusfuehrung>
    <id>49637</id>
    <Herkunft>554</Herkunft>
    <Datum>2019-11-16T00:00:00</Datum>
    <verrechnet>0</verrechnet>
  </DatumAusfuehrung>
</dataroot>
```

### Befehle

```bash
# Dry-Run (nur 2024+2025)
php artisan import:timeline --dry-run

# Import nur 2024+2025 (Standard)
php artisan import:timeline

# Import ab 2020
php artisan import:timeline --min-jahr=2020

# Alle importieren (ohne Duplikat-Check)
php artisan import:timeline --force
```

### Mapping

| XML-Feld | Timeline-Feld | Logik |
|----------|---------------|-------|
| `Herkunft` | `gebaeude_id` | Lookup via legacy_id/legacy_mid |
| `Datum` | `datum` | ISO-Format parsen |
| `verrechnet` | `verrechnen` | verrechnet=1 → verrechnen=false |
| - | `bemerkung` | "Import aus Access (ID: X)" |

### Duplikat-Check

Einträge werden übersprungen wenn:
- Gleiches `gebaeude_id` + `datum` bereits existiert
- Mit `--force` wird dieser Check deaktiviert

---

## 🧾 Rechnungs-Import (FatturaPA)

### XML-Format (FatturaPA.xml)

```xml
<dataroot>
  <FatturaPA>
    <id>153</id>
    <ProgressivoInvio>1</ProgressivoInvio>
    <Data>2019-01-25T00:00:00</Data>
    <Numero>1</Numero>
    <herkunft>93</herkunft>
    <Bezahlt>0</Bezahlt>
    <TipoDocumento>2</TipoDocumento>
    <TipoIva>11</TipoIva>
    <RechnungsBetrag>2050</RechnungsBetrag>
    <MwStr>0</MwStr>
    <Rit>82</Rit>
    <Ritenuta>0</Ritenuta>
    <Causale>...</Causale>
    <CIG>...</CIG>
    <DataPagamento>2019-02-25T00:00:00</DataPagamento>
  </FatturaPA>
</dataroot>
```

### Befehle

```bash
# Dry-Run
php artisan import:rechnungen --dry-run

# Import
php artisan import:rechnungen

# Mit Überschreiben
php artisan import:rechnungen --force
```

### Mapping

| XML-Feld | Rechnung-Feld | Logik |
|----------|---------------|-------|
| `id` | `legacy_id` | Für Duplikat-Check |
| `herkunft` | `gebaeude_id` | Lookup via legacy_id/legacy_mid |
| `Data` | `rechnungsdatum` | ISO-Format |
| `Numero` | `laufnummer` | |
| `Bezahlt` | `status` | 0→sent, 1→paid |
| `TipoDocumento` | `typ_rechnung` | 1→gutschrift, 2→rechnung |
| `TipoIva` | MwSt-Settings | Siehe unten |

### TipoIva Mapping

| TipoIva | MwSt | Split | Reverse |
|---------|------|-------|---------|
| 7 | 10% | ❌ | ❌ |
| 8 | 22% | ❌ | ❌ |
| 9 | 22% | ✅ | ❌ |
| 10 | 0% | ❌ | ❌ |
| 11 | 0% | ❌ | ✅ |
| 12 | 10% | ✅ | ❌ |

### TipoDocumento Mapping

| TipoDocumento | Codex | Typ |
|---------------|-------|-----|
| 1 | TD04 | Gutschrift |
| 2 | TD01 | Rechnung |

### Snapshots

Die Snapshot-Daten werden aus **bereits importierten** Daten geladen:
- `geb_codex`, `geb_name` → aus Gebäude
- `re_*` → aus Rechnungsempfänger (Adresse)
- `post_*` → aus Postadresse (Adresse)

---

## 🚀 Kompletter Import-Workflow

### 1. XML-Dateien auf Server hochladen

```powershell
# Windows PowerShell
$files = @("Adressen.xml", "GebaeudeAbfrage.xml", "DatumAusfuehrung.xml", "FatturaPA.xml", "ArtikelGebaeude.xml")

foreach ($f in $files) {
    scp -P 65002 "C:\...\$f" u192633638@212.1.209.26:~/domains/reschc.space/public_html/storage/import/
}
```

### 2. Auf Server verbinden

```bash
ssh -p 65002 u192633638@212.1.209.26
cd ~/domains/reschc.space/public_html
```

### 3. Imports ausführen (Reihenfolge beachten!)

```bash
# 1. Adressen
php artisan import:access storage/import/Adressen.xml --adressen --dry-run
php artisan import:access storage/import/Adressen.xml --adressen

# 2. Gebäude
php artisan import:access storage/import/GebaeudeAbfrage.xml --gebaeude --dry-run
php artisan import:access storage/import/GebaeudeAbfrage.xml --gebaeude

# 3. Timeline (nur 2024+2025)
php artisan import:timeline storage/import/DatumAusfuehrung.xml --dry-run
php artisan import:timeline storage/import/DatumAusfuehrung.xml

# 4. Rechnungen
php artisan import:rechnungen storage/import/FatturaPA.xml --dry-run
php artisan import:rechnungen storage/import/FatturaPA.xml

# 5. Artikel/Positionen
php artisan import:access storage/import/ArtikelGebaeude.xml --positionen --dry-run
php artisan import:access storage/import/ArtikelGebaeude.xml --positionen
```

---

## 📁 Neue Dateien

```
app/
├── Console/Commands/
│   ├── ImportTimelineCommand.php      🆕
│   └── ImportRechnungenCommand.php    🆕
└── Services/Import/
    ├── TimelineImportService.php      🆕
    └── RechnungImportService.php      🆕
```

### Installation

```bash
# Dateien kopieren
cp TimelineImportService.php app/Services/Import/
cp RechnungImportService.php app/Services/Import/
cp ImportTimelineCommand.php app/Console/Commands/
cp ImportRechnungenCommand.php app/Console/Commands/
```

---

## ⚠️ Wichtige Hinweise

### Gebäude-Lookup

Beide Services (Timeline + Rechnungen) suchen Gebäude über:
1. `legacy_id` (alte Access-ID)
2. `legacy_mid` (alte Access-MID)

```php
$gebaeudeId = $this->gebaeudeMapById[$herkunft]  // legacy_id
           ?? $this->gebaeudeMap[$herkunft]      // legacy_mid
           ?? null;
```

### Timeline: Jahr-Filter

Standard: Nur 2024 + 2025 werden importiert.
Ändern mit: `--min-jahr=2020`

### Rechnungen: Snapshots

Die Adress-Snapshots (re_*, post_*) kommen aus den **bereits importierten** Adressen, NICHT aus dem XML!

### Datum-Formate

Unterstützt:
- ISO: `2019-01-25T00:00:00`
- Deutsch: `25.01.2019`
- Dummy (ignoriert): `2001-01-01`

### Beträge

Unterstützt:
- Zahl: `2050`, `819.67`
- Deutsch: `1.234,56 €`
