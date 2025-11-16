# 🧾 Rechnungs-Modul für Laravel

Vollständiges CRUD-System für Rechnungsverwaltung mit FatturaPA-Support (Italien).

## 📦 Erstellte Dateien

```
app/Http/Controllers/
└── RechnungController.php          # Haupt-Controller mit CRUD & Positionen

resources/views/rechnung/
├── index.blade.php                  # Übersicht (bereits vorhanden)
├── form.blade.php                   # Formular (bereits vorhanden)
├── show.blade.php                   # Detailansicht (NEU)
└── partials/
    ├── _allgemein.blade.php         # Tab 1: Basisdaten (bereits vorhanden)
    ├── _adressen.blade.php          # Tab 2: Rechnungsempfänger & Post (NEU)
    ├── _positionen.blade.php        # Tab 3: Rechnungspositionen (NEU)
    ├── _vorschau.blade.php          # Tab 4: Zusammenfassung (NEU)
    └── _position_edit_modal.blade.php # Modal für Position bearbeiten (NEU)

routes/
└── web.php                          # Erweiterte Routes (NEU)
```

## 🚀 Installation

### 1️⃣ Dateien kopieren

```bash
# Controller
cp app/Http/Controllers/RechnungController.php \
   [DEIN_PROJEKT]/app/Http/Controllers/

# Views (Partials)
cp -r resources/views/rechnung/partials/* \
   [DEIN_PROJEKT]/resources/views/rechnung/partials/

# Show-View
cp resources/views/rechnung/show.blade.php \
   [DEIN_PROJEKT]/resources/views/rechnung/

# Routes erweitern
# WICHTIG: Füge die Rechnung-Routes aus routes/web.php 
# in deine bestehende web.php ein (vor "Auth Scaffolding")
```

### 2️⃣ Routes-Import hinzufügen

Füge in deiner `routes/web.php` **ganz oben** hinzu:

```php
use App\Http\Controllers\RechnungController;
```

Füge dann **vor** `require __DIR__ . '/auth.php';` diesen Block ein:

```php
/* ==================== Rechnungen ==================== */
Route::middleware(['auth', 'verified'])
    ->prefix('rechnung')
    ->name('rechnung.')
    ->group(function () {
        // ... (siehe routes/web.php)
    });
```

### 3️⃣ Cache leeren

```bash
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

### 4️⃣ Testen

Navigiere zu: `http://deine-app.test/rechnung`

## ✨ Features

### ✅ Implementiert

- **CRUD**: Erstellen, Bearbeiten, Löschen von Rechnungen
- **Snapshot-System**: Adressen & Profile werden eingefroren
- **Automatische Erstellung**: Rechnung aus Gebäude generieren
- **Positionsverwaltung**: 
  - Hinzufügen/Bearbeiten/Löschen von Positionen
  - Automatische Berechnung (Netto/MwSt/Brutto)
  - Observer-Pattern für Live-Updates
- **Status-Management**: Draft → Sent → Paid
- **FatturaPA-Support**: 
  - Split Payment
  - Ritenuta d'acconto (4%)
  - CUP/CIG/Auftrags-Daten
- **Filter & Suche**: Jahr, Status, Gebäude, Freitext
- **Tab-Navigation**: Persistenz über localStorage
- **Validierung**: Umfassende Form-Validation

### 🔄 Vorbereitet (TODO)

- **PDF-Export**: `generatePdf()` Methode vorhanden
- **FatturaPA XML**: `generateXml()` Methode vorhanden

## 🎯 Verwendung

### Rechnung aus Gebäude erstellen

```php
$gebaeude = Gebaeude::find(1);
$rechnung = $gebaeude->createRechnung([
    'rechnungsdatum' => now(),
    'status' => 'draft',
]);
```

### Manuelle Position hinzufügen

```php
$rechnung->positionen()->create([
    'position' => 1,
    'beschreibung' => 'Reinigungsarbeiten',
    'anzahl' => 10,
    'einheit' => 'Std',
    'einzelpreis' => 25.00,
    'mwst_satz' => 22.00,
]);

// Beträge automatisch neu berechnen
$rechnung->recalculate();
```

### Status ändern

```php
// Von Draft zu Sent
$rechnung->update(['status' => 'sent']);

// Als bezahlt markieren
$rechnung->update([
    'status' => 'paid',
    'bezahlt_am' => now(),
]);
```

### Überfällige Rechnungen finden

```php
$ueberfaellig = Rechnung::overdue()->get();
```

## 🔐 Berechtigungen

- **Entwürfe (draft)**: Voll editierbar
- **Versendet/Bezahlt/Storniert**: Nur lesbar
- Löschen nur bei Status `draft`

## 📊 Berechnungen

### Automatische Berechnung (Observer)

RechnungPosition berechnet bei jedem Speichern:

```
Netto = Anzahl × Einzelpreis
MwSt  = Netto × (MwSt-Satz / 100)
Brutto = Netto + MwSt
```

### Rechnung-Summen

```
Netto-Summe   = Σ(Position.netto_gesamt)
MwSt-Betrag   = Σ(Position.mwst_betrag)
Brutto-Summe  = Netto + MwSt
Ritenuta      = Netto × (Ritenuta % / 100)  [wenn aktiv]
Zahlbar       = Brutto - Ritenuta
```

## 🇮🇹 FatturaPA (Italien)

### Profile

Erstelle FatturaProfile mit:

```php
FatturaProfile::create([
    'bezeichnung' => 'Standard 22%',
    'mwst_satz' => 22.00,
    'split_payment' => false,
    'ritenuta' => false,
]);
```

### Split Payment

Bei aktiviertem Split Payment wird die MwSt separat behandelt:
- Kunde zahlt nur Netto
- MwSt geht direkt an Finanzamt

### Ritenuta d'acconto

Quellensteuer (typisch 4%):
- Wird vom Netto-Betrag abgezogen
- Standard: 4% (konfigurierbar)

## 🐛 Troubleshooting

### Routes werden nicht gefunden

```bash
php artisan route:list --name=rechnung
php artisan route:clear
```

### View nicht gefunden

```bash
# Prüfe ob Partials existieren:
ls -la resources/views/rechnung/partials/

# Cache leeren:
php artisan view:clear
```

### Positionen werden nicht berechnet

Observer prüfen:

```php
// In RechnungPosition.php
protected static function booted(): void
{
    static::saving(function (RechnungPosition $position) {
        $position->calculateAmounts();
    });
}
```

## 📝 Nächste Schritte

1. **PDF-Export implementieren**:
   ```bash
   composer require barryvdh/laravel-dompdf
   ```

2. **FatturaPA XML generieren**:
   - Siehe offizielle Spezifikation
   - Validierung gegen XSD-Schema

3. **E-Mail-Versand**:
   ```php
   Mail::to($rechnung->post_email)->send(new RechnungMail($rechnung));
   ```

4. **Zahlungs-Tracking**:
   - Zahlungseingänge erfassen
   - Mahnwesen implementieren

## 📚 Dokumentation

- **Models**: Siehe Kommentare in `Rechnung.php` und `RechnungPosition.php`
- **Controller**: Alle Methoden sind dokumentiert
- **Views**: Blade-Kommentare erklären Struktur

## 🎨 UI/UX

- **Bootstrap 5** (Icons: Bootstrap Icons)
- **Responsive Design**
- **Tab-Navigation** mit localStorage-Persistenz
- **Modals** für Positionen
- **Flash-Messages** für Feedback

## ⚡ Performance

- **Eager Loading**: Relations werden vorgeladen
- **Pagination**: 50 Einträge pro Seite
- **Optimierte Queries**: Nur benötigte Felder laden
- **Observer**: Automatische Berechnung ohne N+1 Problem

---

**Erstellt für:** Laravel Rechnungssystem mit FatturaPA-Support  
**Kompatibel mit:** Laravel 10+  
**Datenbank:** MySQL/MariaDB
