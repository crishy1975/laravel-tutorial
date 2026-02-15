<?php
// ══════════════════════════════════════════════════════════════════════════════
// ERWEITERUNG FÜR: app/Models/GebaeudeLog.php
// ══════════════════════════════════════════════════════════════════════════════

// Füge diese Methoden zur GebaeudeLog Klasse hinzu (im Bereich "FINANZEN"):

// ═══════════════════════════════════════════════════════════════════════════════
// 🗑️ RECHNUNG GELÖSCHT / WIEDERHERGESTELLT
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Rechnung gelöscht loggen (mit Wiederherstellungs-Möglichkeit)
 */
public static function rechnungGeloescht(
    int $gebaeudeId,
    string $rechnungsnummer,
    float $betrag,
    string $loeschgrund,
    int $rechnungId
): self {
    return self::create([
        'gebaeude_id'  => $gebaeudeId,
        'typ'          => GebaeudeLogTyp::RECHNUNG_GELOESCHT,
        'titel'        => "Rechnung {$rechnungsnummer} gelöscht",
        'beschreibung' => $loeschgrund,
        'user_id'      => Auth::id(),
        'prioritaet'   => 'hoch',
        'referenz_id'  => $rechnungId,
        'referenz_typ' => 'rechnung',
        'metadata'     => [
            'rechnung_id'     => $rechnungId,
            'rechnungsnummer' => $rechnungsnummer,
            'betrag'          => $betrag,
            'loeschgrund'     => $loeschgrund,
            'geloescht_von'   => Auth::user()?->name ?? 'System',
            'geloescht_am'    => now()->format('Y-m-d H:i:s'),
            // ⭐ Wichtig für Wiederherstellung
            'kann_wiederhergestellt_werden' => true,
        ],
    ]);
}

/**
 * Rechnung wiederhergestellt loggen
 */
public static function rechnungWiederhergestellt(
    int $gebaeudeId,
    string $rechnungsnummer,
    int $rechnungId
): self {
    return self::log(
        $gebaeudeId,
        GebaeudeLogTyp::RECHNUNG_WIEDERHERGESTELLT,
        "Rechnung {$rechnungsnummer} wurde wiederhergestellt",
        [
            'rechnung_id'            => $rechnungId,
            'rechnungsnummer'        => $rechnungsnummer,
            'wiederhergestellt_von'  => Auth::user()?->name ?? 'System',
            'wiederhergestellt_am'   => now()->format('Y-m-d H:i:s'),
        ],
        "Rechnung {$rechnungsnummer} wiederhergestellt"
    );
}

// ═══════════════════════════════════════════════════════════════════════════════
// 🔍 SCOPE FÜR GELÖSCHTE RECHNUNGEN
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Nur gelöschte Rechnungen (die wiederhergestellt werden können)
 */
public function scopeGeloeschteRechnungen($query)
{
    return $query->where('typ', GebaeudeLogTyp::RECHNUNG_GELOESCHT->value)
                 ->whereJsonContains('metadata->kann_wiederhergestellt_werden', true);
}

// ═══════════════════════════════════════════════════════════════════════════════
// 🔧 HILFSMETHODE: Wiederherstellungs-Markierung entfernen
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Nach Wiederherstellung: Flag im Log-Eintrag aktualisieren
 */
public function markiereAlsWiederhergestellt(): bool
{
    $metadata = $this->metadata ?? [];
    $metadata['kann_wiederhergestellt_werden'] = false;
    $metadata['wiederhergestellt_am'] = now()->format('Y-m-d H:i:s');
    
    return $this->update(['metadata' => $metadata]);
}
