storage-link-fix.sh#!/bin/bash
# Storage Link Fix Script

echo "🔗 STORAGE LINK FIX"
echo "==================="
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 1. Prüfe ob public/storage existiert
echo "1️⃣  Prüfe public/storage..."
if [ -e public/storage ]; then
    if [ -L public/storage ]; then
        echo -e "${YELLOW}⚠️  Symlink existiert bereits${NC}"
        ls -la public/storage
        
        # Prüfe ob Link korrekt ist
        TARGET=$(readlink public/storage)
        echo "   Zeigt auf: $TARGET"
        
        if [ -d "$TARGET" ]; then
            echo -e "${GREEN}✅ Ziel existiert${NC}"
        else
            echo -e "${RED}❌ Ziel existiert NICHT! Link ist kaputt!${NC}"
            echo "   Lösche kaputten Link..."
            rm public/storage
        fi
    else
        echo -e "${RED}❌ public/storage existiert als Datei/Ordner (kein Symlink!)${NC}"
        echo "   Lösche..."
        rm -rf public/storage
    fi
else
    echo -e "${YELLOW}⚠️  public/storage existiert nicht${NC}"
fi

# 2. Erstelle Storage Link neu
echo ""
echo "2️⃣  Erstelle Storage Link..."
php artisan storage:link

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Storage Link erstellt${NC}"
else
    echo -e "${RED}❌ Fehler beim Erstellen!${NC}"
fi

# 3. Prüfe Ergebnis
echo ""
echo "3️⃣  Prüfe Ergebnis..."
if [ -L public/storage ]; then
    TARGET=$(readlink public/storage)
    echo -e "${GREEN}✅ Symlink existiert${NC}"
    echo "   public/storage → $TARGET"
    
    # Prüfe ob Ziel existiert
    if [ -d "$TARGET" ]; then
        echo -e "${GREEN}✅ Ziel-Verzeichnis existiert${NC}"
    else
        echo -e "${RED}❌ Ziel-Verzeichnis fehlt!${NC}"
        echo "   Erstelle: $TARGET"
        mkdir -p "$TARGET"
    fi
else
    echo -e "${RED}❌ Symlink fehlt immer noch!${NC}"
fi

# 4. Prüfe Logos-Verzeichnis
echo ""
echo "4️⃣  Prüfe Logos-Verzeichnis..."
if [ -d storage/app/public/logos ]; then
    echo -e "${GREEN}✅ storage/app/public/logos existiert${NC}"
    
    # Zähle Dateien
    FILE_COUNT=$(ls -1 storage/app/public/logos 2>/dev/null | wc -l)
    echo "   Dateien: $FILE_COUNT"
    
    if [ $FILE_COUNT -gt 0 ]; then
        echo "   Letzte Dateien:"
        ls -lht storage/app/public/logos | head -4
    fi
else
    echo -e "${RED}❌ storage/app/public/logos fehlt!${NC}"
    echo "   Erstelle Verzeichnis..."
    mkdir -p storage/app/public/logos
    chmod -R 775 storage/app/public/logos
fi

# 5. Teste Zugriff
echo ""
echo "5️⃣  Test-Zugriff..."
LOGO_FILE=$(ls storage/app/public/logos/*.jpg storage/app/public/logos/*.png 2>/dev/null | head -1)

if [ -n "$LOGO_FILE" ]; then
    FILENAME=$(basename "$LOGO_FILE")
    echo "   Test-Datei: $FILENAME"
    
    # Prüfe ob über public/storage erreichbar
    if [ -f "public/storage/logos/$FILENAME" ]; then
        echo -e "${GREEN}✅ Datei über public/storage erreichbar${NC}"
        echo "   URL: http://localhost:8000/storage/logos/$FILENAME"
    else
        echo -e "${RED}❌ Datei NICHT über public/storage erreichbar!${NC}"
        echo "   Erwartet: public/storage/logos/$FILENAME"
    fi
else
    echo -e "${YELLOW}⚠️  Keine Logo-Datei gefunden zum Testen${NC}"
fi

# Zusammenfassung
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 ZUSAMMENFASSUNG"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Storage Link-Struktur:"
echo "  public/storage → storage/app/public"
echo ""
echo "Logo-Pfad:"
echo "  Speicher: storage/app/public/logos/logo.jpg"
echo "  Web-URL:  http://localhost:8000/storage/logos/logo.jpg"
echo ""
echo "Nächste Schritte:"
echo "  1. Browser neu laden (Strg+F5)"
echo "  2. Logo sollte jetzt sichtbar sein!"
echo ""
echo "Falls immer noch Fehler:"
echo "  → Prüfe Browser Console (F12)"
echo "  → Öffne direkt: http://localhost:8000/storage/logos/[DATEINAME]"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
