# KI-Coding-Anweisungen für Laravel Gebäudemanagement-System

## Projektübersicht

Dies ist ein Laravel-basiertes Gebäudemanagement-System für Reinigungsdienste, das folgende Bereiche abdeckt:

-   Gebäudeverwaltung mit Adressierung
-   Tourenplanung und -zeitplanung
-   Timeline-Tracking für Reinigungsaktivitäten
-   Rechnungsverwaltung und Abrechnung

## Wichtige Architekturkomponenten

### Domänenmodelle

-   `Gebaeude`: Zentrales Gebäudemodell mit Reinigungszeitplänen (`m01`-`m12` Flags)
-   `Adresse`: Adressverwaltung (sowohl für Post- als auch Rechnungsadressen)
-   `Tour`: Verwaltung der Reinigungstouren
-   `Timeline`: Aktivitätsverfolgung für Gebäude

### Projektstruktur-Muster

1. Models (`app/Models/`):

    - Verwendung von Soft-Deletes zur Datenerhaltung
    - Implementierung von Boolean-Flags als entsprechende Casts
    - Strikte Typisierung von Beziehungen (BelongsTo, HasMany, etc.)

2. Datenbank:
    - Migrationen in `database/migrations/` folgen Zeitstempel-Benennung
    - Verwendung von Factories (`database/factories/`) für Testdaten

## Entwicklungs-Workflow

### Setup-Befehle

```bash
# Ersteinrichtung
composer run-script setup

# Entwicklungsumgebung
composer run-script dev  # Startet Server, Queue-Listener und Vite
```

### Tests

```bash
composer test  # Führt PHPUnit/Pest Tests aus
```

## Projektspezifische Muster

### Datumshandling

-   Verwendung von Carbon-Instanzen für alle Datums-/Zeitoperationen
-   Gebäude-Fälligkeitsdaten (`faellig`) werden berechnet basierend auf:
    -   Aktiven Monatsflags (`m01`-`m12`)
    -   Letztem Reinigungsdatum aus der Timeline

### Beziehungen

-   Gebäude (`Gebaeude`) haben:
    -   Postadresse (`postadresse`)
    -   Rechnungsadresse (`rechnungsempfaenger`)
    -   Viele-zu-viele Touren mit geordneten Positionen
    -   Timeline-Einträge für Reinigungshistorie

## Test-Muster

-   Nutzung des Pest PHP Testing Frameworks
-   Factories verfügbar für Hauptentitäten:
    -   `AdresseFactory`
    -   `GebaeudeFactory`
    -   `TourFactory`

## Wichtige Referenzdateien

-   `app/Models/Gebaeude.php`: Kernlogik der Gebäudeverwaltung
-   `app/Services/FaelligkeitsService.php`: Fälligkeitsberechnungs-Service
-   `database/migrations/`: Datenbankstruktur und -änderungen
-   `composer.json`: Projekt-Dependencies und Scripts
