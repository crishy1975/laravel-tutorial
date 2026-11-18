<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Gemeinde extends Model
{
    protected $table = 'gemeinden';

    protected $fillable = [
        'name',
        'aliase',
        'pec',
        'email',
        'betreff',
        'text',
        'aktiv',
    ];

    protected $casts = [
        'aktiv' => 'boolean',
    ];

    public const STANDARD_BETREFF = 'Nicht erreichte Kunden – Kaminkehrung / Utenti non raggiungibili – pulizia camini';

    public const STANDARD_TEXT = <<<'TXT'
Sehr geehrter Herr Bürgermeister,

wir teilen Ihnen mit, dass wir in der Gemeinde {GEMEINDE} folgende {ANZAHL} Kunden trotz Ankündigung für die vorgeschriebene Kaminkehrung nicht erreichen konnten:

{LISTE}

Wir bitten um Kenntnisnahme.

Mit freundlichen Grüßen
Resch GmbH

------------------------------------------------------------

Egregio Signor Sindaco,

con la presente comunichiamo che nel Comune di {GEMEINDE}, nonostante il preavviso, non è stato possibile raggiungere i seguenti {ANZAHL} utenti per la pulizia obbligatoria dei camini:

{LISTE}

Distinti saluti
Resch S.r.l.

Galvanistr. 6 / via Galvani 6 – 39100 Bozen/Bolzano
info@resch.bz – resch@pec.it
{DATUM}
TXT;

    public function meldungen(): HasMany
    {
        return $this->hasMany(GemeindeMeldung::class);
    }

    /** Empfänger: PEC bevorzugt, sonst E-Mail */
    public function getEmpfaengerAttribute(): ?string
    {
        return $this->pec ?: ($this->email ?: null);
    }

    /** Alle Namen dieser Gemeinde (Name + Aliase), klein geschrieben */
    public function namen(): array
    {
        $namen = [$this->name];
        foreach (explode(',', (string) $this->aliase) as $a) {
            $namen[] = $a;
        }
        return array_values(array_filter(array_map(fn ($n) => static::normalisiere($n), $namen)));
    }

    public static function normalisiere(?string $s): string
    {
        return mb_strtolower(trim((string) $s));
    }

    /**
     * Passende Gemeinde zu einem Ort finden.
     * $gemeinden: vorgeladene Collection (spart Abfragen bei Listen).
     */
    public static function fuerOrt(?string $ort, ?Collection $gemeinden = null): ?self
    {
        $ort = static::normalisiere($ort);
        if ($ort === '') {
            return null;
        }

        $gemeinden ??= static::where('aktiv', true)->get();

        return $gemeinden->first(fn (self $g) => in_array($ort, $g->namen(), true));
    }

    /** Betreff (Vorlage der Gemeinde oder Standard) */
    public function betreffVorlage(): string
    {
        return $this->betreff ?: self::STANDARD_BETREFF;
    }

    /** Text mit ersetzten Platzhaltern */
    public function textFuer(Collection $gebaeude): string
    {
        return static::ersetzePlatzhalter($this->text ?: self::STANDARD_TEXT, $this->name, $gebaeude);
    }

    public static function ersetzePlatzhalter(string $vorlage, string $gemeindeName, Collection $gebaeude): string
    {
        return strtr($vorlage, [
            '{GEMEINDE}' => $gemeindeName,
            '{ANZAHL}'   => (string) $gebaeude->count(),
            '{LISTE}'    => static::liste($gebaeude),
            '{DATUM}'    => now()->format('d.m.Y'),
        ]);
    }

    /** Liste der Gebäude als Text */
    public static function liste(Collection $gebaeude): string
    {
        return $gebaeude->map(function ($g) {
            $adresse = trim($g->strasse . ' ' . $g->hausnummer);
            if ($g->intern) {
                $adresse .= ' / Int. ' . $g->intern;
            }
            $adresse .= ', ' . trim($g->plz . ' ' . $g->wohnort);

            $teile = ['- ' . ($g->gebaeude_name ?: $g->codex), $adresse];

            if ($g->angekuendigt_fuer) {
                $teile[] = 'angekündigt für / preavvisato per ' . Carbon::parse($g->angekuendigt_fuer)->format('d.m.Y');
            }

            return implode(' | ', $teile);
        })->implode("\n");
    }
}
