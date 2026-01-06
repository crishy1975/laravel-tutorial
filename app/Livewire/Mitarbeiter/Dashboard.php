<?php

namespace App\Livewire\Mitarbeiter;

use App\Models\Lohnstunde;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        
        // Statistiken für den Mitarbeiter
        $stundenHeute = Lohnstunde::where('user_id', $user->id)
            ->heute()
            ->sum('stunden');

        $stundenDieseWoche = Lohnstunde::where('user_id', $user->id)
            ->dieseWoche()
            ->sum('stunden');

        $stundenDiesenMonat = Lohnstunde::where('user_id', $user->id)
            ->monat(now()->month, now()->year)
            ->sum('stunden');

        // Letzte Einträge
        $letzteEintraege = Lohnstunde::where('user_id', $user->id)
            ->orderBy('datum', 'desc')
            ->limit(5)
            ->get();

        return view('livewire.mitarbeiter.dashboard', [
            'stundenHeute' => $stundenHeute,
            'stundenDieseWoche' => $stundenDieseWoche,
            'stundenDiesenMonat' => $stundenDiesenMonat,
            'letzteEintraege' => $letzteEintraege,
        ])->layout('layouts.mitarbeiter');
    }
}
