{{--
════════════════════════════════════════════════════════════════════════════
DATEI: registro-kopf.blade.php
PFAD:  resources/views/pdf/partials/registro-kopf.blade.php
(Briefkopf – nutzt $firma und $logo aus registro-corrispettivi.blade.php)
════════════════════════════════════════════════════════════════════════════
--}}
<table class="kopf">
    <tr>
        <td>
            <div class="firma">{{ $firma['name'] }}</div>
            <div class="unter">{{ $firma['untertitel'] }}</div>
        </td>
    </tr>
</table>
<div class="absender">{{ $firma['adresse'] }} – {{ $firma['piva'] }}</div>
