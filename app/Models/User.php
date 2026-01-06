<?php

/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: User.php
 * PFAD:  app/Models/User.php
 * ════════════════════════════════════════════════════════════════════════════
 */

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ============================================
    // ROLLEN-METHODEN
    // ============================================

    /**
     * Prüft ob der User ein Admin ist
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Prüft ob der User ein Mitarbeiter ist
     */
    public function isMitarbeiter(): bool
    {
        return $this->role === 'mitarbeiter';
    }

    // ============================================
    // BEZIEHUNGEN
    // ============================================

    /**
     * Lohnstunden des Mitarbeiters
     */
    public function lohnstunden()
    {
        return $this->hasMany(\App\Models\Lohnstunde::class);
    }

    /**
     * Änderungsvorschläge die dieser User erstellt hat
     */
    public function gebaeudeAenderungsvorschlaege()
    {
        return $this->hasMany(\App\Models\GebaeudeAenderungsvorschlag::class, 'user_id');
    }

    /**
     * Änderungsvorschläge die dieser User bearbeitet hat (als Admin)
     */
    public function bearbeiteteAenderungsvorschlaege()
    {
        return $this->hasMany(\App\Models\GebaeudeAenderungsvorschlag::class, 'bearbeitet_von');
    }

    /**
     * Ausstehende Änderungsvorschläge dieses Users
     */
    public function ausstehendeAenderungsvorschlaege()
    {
        return $this->hasMany(\App\Models\GebaeudeAenderungsvorschlag::class, 'user_id')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Anzahl ausstehender Änderungsvorschläge
     */
    public function anzahlAusstehendeVorschlaege(): int
    {
        return $this->ausstehendeAenderungsvorschlaege()->count();
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope: Nur Mitarbeiter
     */
    public function scopeMitarbeiter($query)
    {
        return $query->where('role', 'mitarbeiter');
    }

    /**
     * Scope: Nur Admins
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    // ============================================
    // HILFSMETHODEN
    // ============================================

    /**
     * Lohnstunden eines bestimmten Monats
     */
    public function lohnstundenMonat(int $monat, int $jahr)
    {
        return $this->lohnstunden()
            ->whereYear('datum', $jahr)
            ->whereMonth('datum', $monat)
            ->get();
    }

    /**
     * Gesamtstunden eines Monats
     */
    public function gesamtstundenMonat(int $monat, int $jahr): float
    {
        return $this->lohnstunden()
            ->whereYear('datum', $jahr)
            ->whereMonth('datum', $monat)
            ->sum('stunden');
    }
}
