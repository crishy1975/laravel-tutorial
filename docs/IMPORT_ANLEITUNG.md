# 📥 Access-Datenbank Import Anleitung

> Import der alten Access-Datenbank nach Laravel/MySQL

---

## 📋 Übersicht

Der Import überträgt Daten aus XML-Exports der Access-Datenbank in die neue Laravel-Anwendung.

### Unterstützte Tabellen

| Tabelle | XML-Datei | Beschreibung |
|---------|-----------|--------------|
| Adressen | `Adresse.xml` | Kunden, Rechnungsempfänger |
| Gebäude | `Gebaeude.xml` | Objekte mit Reinigungsplan |
| Artikel | `Artikel.xml` | Leistungen pro Gebäude |
| Rechnungen | `FatturaPAXmlAbfrage.xml` | Rechnungsköpfe |
| Positionen | `ArtikelFatturaPAAbfrage.xml` | Rechnungspositionen |

### Import-Reihenfolge (wichtig!)

```
1. Adressen      → Basis für alles
2. Gebäude       → Referenziert Adressen
3. Artikel       → Referenziert Gebäude
4. Rechnungen    → Referenziert Adressen + Gebäude
5. Positionen    → Referenziert Rechnungen + Artikel
```

---

## 🔧 Voraussetzungen

### 1. XML-Dateien aus Access exportieren

In Access: Rechtsklick auf Tabelle → Exportieren → XML

Benötigte Dateien:
- `Adresse.xml`
- `Gebaeude.xml`
- `Artikel.xml`
- `FatturaPAXmlAbfrage.xml`
- `ArtikelFatturaPAAbfrage.xml`

### 2. Lokaler Ordner (für Export aus Access)

```
C:\Users\Christian\Documents\entwicklung\xml-export\
```

### 3. Server-Ordner

```
~/domains/reschc.space/public_html/storage/import/
```

---

## 🚀 Import auf Hostinger (Server)

### Schritt 1: Import-Ordner erstellen

```powershell
ssh -p 65002 u192633638@212.1.209.26 "mkdir -p ~/domains/reschc.space/public_html/storage/import"
```

### Schritt 2: XML-Dateien hochladen

```powershell
scp -P 65002 "C:\Users\Christian\Documents\entwicklung\xml-export\*.xml" u192633638@212.1.209.26:~/domains/reschc.space/public_html/storage/import/
```

### Schritt 3: Import starten

**Alles importieren (empfohlen für Erstimport):**
```powershell
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:access --all"
```

**Einzelne Tabellen:**
```powershell
# Nur Adressen
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:access --adressen"

# Nur Gebäude
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:access --gebaeude"

# Nur Artikel
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:access --artikel"

# Nur Rechnungen
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:access --rechnungen"

# Nur Positionen
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:access --positionen"
```

### Schritt 4: Gebäude-Namen korrigieren

Nach dem Import können fehlende Gebäude-Namen vom Rechnungsempfänger übernommen werden:

```powershell
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:fix-gebaeude"
```

---

## ⚙️ Import-Optionen

### `--all`
Importiert alle Tabellen in der richtigen Reihenfolge.

```powershell
php artisan import:access --all
```

### `--dry-run`
Testlauf ohne Speichern. Zeigt was passieren würde.

```powershell
php artisan import:access --all --dry-run
```

### `--force`
Überschreibt bestehende Einträge (normalerweise werden Duplikate übersprungen).

```powershell
php artisan import:access --all --force
```

### `--path=`
Alternativer Pfad zum XML-Ordner.

```powershell
php artisan import:access --all --path=storage/app/mein-ordner
```

### Kombinationen

```powershell
# Test mit Force
php artisan import:access --all --dry-run --force

# Nur Adressen überschreiben
php artisan import:access --adressen --force
```

---

## 🔄 Gebäude-Namen Fix

### Beschreibung

Füllt fehlende Gebäude-Daten (Name, Straße, PLZ, etc.) mit Daten vom Rechnungsempfänger.

### Befehle

```powershell
# Nur Gebäude ohne Namen fixen
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:fix-gebaeude"

# Erst testen (nur anzeigen)
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:fix-gebaeude --dry-run"

# Alle Gebäude überschreiben (auch mit Namen)
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:fix-gebaeude --force"
```

---

## 💻 Lokaler Import (Entwicklung)

Falls du lokal testen möchtest:

```powershell
# Ordner erstellen
mkdir storage\import

# XML-Dateien kopieren
copy "C:\Users\Christian\Documents\entwicklung\xml-export\*.xml" "storage\import\"

# Import starten
php artisan import:access --all

# Gebäude-Namen fixen
php artisan import:fix-gebaeude
```

---

## 📊 Nach dem Import prüfen

### Anzahl der Einträge

```powershell
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan tinker --execute=\"echo 'Adressen: ' . App\Models\Adresse::count() . PHP_EOL . 'Gebäude: ' . App\Models\Gebaeude::count() . PHP_EOL;\""
```

### Fehler-Log prüfen

Nach dem Import werden Fehler in eine Log-Datei geschrieben:

```powershell
ssh -p 65002 u192633638@212.1.209.26 "ls -la ~/domains/reschc.space/public_html/storage/logs/import_errors_*.log"

# Letzten Fehler-Log anzeigen
ssh -p 65002 u192633638@212.1.209.26 "cat ~/domains/reschc.space/public_html/storage/logs/import_errors_*.log | tail -50"
```

---

## ❗ Troubleshooting

### Problem: "Import-Ordner nicht gefunden"

```powershell
# Ordner erstellen
ssh -p 65002 u192633638@212.1.209.26 "mkdir -p ~/domains/reschc.space/public_html/storage/import"
```

### Problem: "Datei nicht gefunden"

Prüfe ob alle XML-Dateien hochgeladen wurden:

```powershell
ssh -p 65002 u192633638@212.1.209.26 "ls -la ~/domains/reschc.space/public_html/storage/import/"
```

Erwartete Dateien:
```
Adresse.xml
Gebaeude.xml
Artikel.xml
FatturaPAXmlAbfrage.xml
ArtikelFatturaPAAbfrage.xml
```

### Problem: "Foreign Key Constraint"

Importiere in der richtigen Reihenfolge! Nutze `--all` für automatische Reihenfolge.

### Problem: "Duplicate Entry"

Ohne `--force` werden bestehende Einträge übersprungen. Das ist normal!

Falls du neu importieren willst:

```powershell
# Mit --force überschreiben
php artisan import:access --all --force
```

### Problem: "Memory Limit"

Bei sehr großen XML-Dateien:

```powershell
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php -d memory_limit=512M artisan import:access --all"
```

---

## 📁 Datei-Referenz

### Import-Command
```
app/Console/Commands/ImportAccessData.php
```

### Fix-Command
```
app/Console/Commands/FixGebaeudeNamen.php
```

### Import-Service
```
app/Services/Import/AccessImportService.php
```

---

## 🔗 Quick Reference (Kopiervorlagen)

### Kompletter Erstimport

```powershell
# 1. Ordner erstellen
ssh -p 65002 u192633638@212.1.209.26 "mkdir -p ~/domains/reschc.space/public_html/storage/import"

# 2. Dateien hochladen
scp -P 65002 "C:\Users\Christian\Documents\entwicklung\xml-export\*.xml" u192633638@212.1.209.26:~/domains/reschc.space/public_html/storage/import/

# 3. Import starten
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:access --all"

# 4. Namen fixen
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:fix-gebaeude"
```

### Aktualisierung (mit Überschreiben)

```powershell
scp -P 65002 "C:\Users\Christian\Documents\entwicklung\xml-export\*.xml" u192633638@212.1.209.26:~/domains/reschc.space/public_html/storage/import/

ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:access --all --force"
```

---

---

## 🖼️ Logo hochladen

Das Firmenlogo für Rechnungen muss manuell hochgeladen werden:

```powershell
# Logo in den public-Ordner hochladen
scp -P 65002 "C:\Pfad\zum\logo.png" u192633638@212.1.209.26:~/domains/reschc.space/public_html/public/images/logo.png
```

**Alternativ:** Über die Web-Oberfläche im Unternehmensprofil hochladen.

---

## 🧾 Rechnungsprofile (FatturaPA)

Die FatturaPA-Profile für italienische Rechnungen müssen eingerichtet werden:

### Option 1: Über Web-Oberfläche

1. Einloggen auf https://reschc.space
2. Menü → Einstellungen → Fattura-Profile
3. Neues Profil erstellen mit:
   - Bezeichnung (z.B. "Standard 22%")
   - MwSt-Satz
   - Split Payment (ja/nein)
   - Ritenuta (Quellensteuer)

### Option 2: Via Tinker (manuell)

```powershell
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan tinker"
```

```php
// Standard-Profil erstellen
App\Models\FatturaProfile::create([
    'bezeichnung' => 'Standard 22%',
    'mwst_satz' => 22.00,
    'split_payment' => false,
    'ritenuta' => false,
]);

// Split Payment Profil (öffentliche Auftraggeber)
App\Models\FatturaProfile::create([
    'bezeichnung' => 'Split Payment 22%',
    'mwst_satz' => 22.00,
    'split_payment' => true,
    'ritenuta' => false,
]);

exit
```

---

## 📦 DomPDF installieren (für PDF-Generierung)

Falls PDF-Fehler auftreten ("Class Pdf not found"):

```powershell
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && composer require barryvdh/laravel-dompdf"

ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan config:clear && php artisan cache:clear"
```

---

## 🔗 Vollständiger Erstimport (alle Schritte)

```powershell
# 1. Import-Ordner erstellen
ssh -p 65002 u192633638@212.1.209.26 "mkdir -p ~/domains/reschc.space/public_html/storage/import"

# 2. XML-Dateien hochladen
scp -P 65002 "C:\Users\Christian\Documents\entwicklung\xml-export\*.xml" u192633638@212.1.209.26:~/domains/reschc.space/public_html/storage/import/

# 3. Import starten
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:access --all"

# 4. Gebäude-Namen fixen
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan import:fix-gebaeude"

# 5. Logo hochladen
scp -P 65002 "C:\Pfad\zum\logo.png" u192633638@212.1.209.26:~/domains/reschc.space/public_html/public/images/logo.png

# 6. DomPDF installieren (falls nicht vorhanden)
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && composer require barryvdh/laravel-dompdf"

# 7. Cache leeren
ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan config:clear && php artisan cache:clear"
```

**Danach manuell:**
- Rechnungsprofile über Web-Oberfläche erstellen
- Unternehmensprofil ausfüllen

---

*Letzte Aktualisierung: Dezember 2024*
