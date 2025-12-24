{{-- resources/views/willkommen.blade.php --}}
<!DOCTYPE html>
<html lang="de">
<x-head/>
<body class="bg-light">

    {{-- Navigation --}}
    <x-navbar active="willkommen"/>

    {{-- Hauptinhalt --}}
    <div class="container">
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
            <div class="container-fluid py-5">
                <h1 class="display-5 fw-bold">Willkommen bei Resch GmbH Meisterbetrieb</h1>
                <p class="col-md-8 fs-5 mt-3">
                    Dies ist deine erste eigene Laravel-Seite mit Bootstrap 🎉  
                    Du kannst hier Texte, Tabellen oder Buttons hinzufügen – alles mit Bootstrap-Styling.
                </p>
                <a href="/dashboard" class="btn btn-primary btn-lg mt-3">
                    <i class="bi bi-speedometer2"></i> Zum Dashboard
                </a>
            </div>
        </div>

        {{-- Beispielkarte --}}
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-fire"></i> Kaminanlagen</h5>
                        <p class="card-text">Verwalte deine Kunden- und Gebäudedaten digital und effizient.</p>
                        <a href="#" class="btn btn-outline-primary btn-sm">Mehr erfahren</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-calendar-check"></i> Termine</h5>
                        <p class="card-text">Plane und dokumentiere Wartungen direkt im System.</p>
                        <a href="#" class="btn btn-outline-primary btn-sm">Zur Timeline</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-person-lines-fill"></i> Kunden</h5>
                        <p class="card-text">Alle Kundendaten und Rechnungen an einem Ort verwalten.</p>
                        <a href="#" class="btn btn-outline-primary btn-sm">Zur Übersicht</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
