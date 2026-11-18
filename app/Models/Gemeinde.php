<?php
// ═══════════════════════════════════════════════════════════════
// ZIEL: app/Models/Gemeinde.php  (komplett ersetzen)
// ═══════════════════════════════════════════════════════════════

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

    public const STANDARD_BETREFF = 'Meldung gemäß Art. 42 Abs. 5 Buchst. c) DLH Nr. 27/2009 – Überprüfung und Reinigung von Feuerungsanlagen nicht möglich / Segnalazione ai sensi dell\'art. 42, comma 5, lett. c) DPP n. 27/2009 – controllo e pulizia impianti di combustione non eseguiti';

    public const STANDARD_TEXT = <<<'TXT'
An den Bürgermeister / die Bürgermeisterin der Gemeinde {GEMEINDE}

Sehr geehrte Damen und Herren,

im Sinne der Handwerksordnung (Landesgesetz vom 25. Februar 2008, Nr. 1)
und der Durchführungsverordnung (Dekret des Landeshauptmanns vom 19. Mai 2009,
Nr. 27, Art. 42 Abs. 5 Buchst. c) melden wir Ihnen folgende {ANZAHL} Benützer
von Feuerungsanlagen, bei denen die vorgeschriebene Überprüfung und Reinigung
nicht durchgeführt werden konnte.

Die Termine wurden gemäß Art. 43 Abs. 1 der genannten Verordnung
ordnungsgemäß angekündigt. Die Benützer haben die Überprüfung und Reinigung
verweigert bzw. wurden trotz mehrfacher Versuche nicht angetroffen, ohne
einen neuen Termin zu vereinbaren.

{LISTE}

Wir ersuchen um Kenntnisnahme und um Veranlassung der erforderlichen
Maßnahmen. Für Rückfragen stehen wir gerne zur Verfügung.

Mit freundlichen Grüßen
Christian Resch – Resch GmbH

------------------------------------------------------------

Al Sindaco / Alla Sindaca del Comune di {GEMEINDE}

Gentili Signore e Signori,

ai sensi dell'Ordinamento dell'artigianato (legge provinciale 25 febbraio
2008, n. 1) e del relativo regolamento di esecuzione (decreto del Presidente
della Provincia 19 maggio 2009, n. 27, art. 42, comma 5, lett. c)
segnaliamo i seguenti {ANZAHL} utenti di impianti di combustione presso i
quali non è stato possibile eseguire il controllo e la pulizia prescritti.

Gli interventi sono stati regolarmente preavvisati ai sensi dell'art. 43,
comma 1, del citato regolamento. Gli utenti hanno rifiutato il controllo e
la pulizia ovvero, nonostante ripetuti tentativi, non sono stati reperiti e
non hanno concordato un nuovo appuntamento.

{LISTE}

Chiediamo di prenderne atto e di disporre i provvedimenti necessari.
Restiamo a disposizione per eventuali chiarimenti.

Distinti saluti
Christian Resch – Resch S.r.l.

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
