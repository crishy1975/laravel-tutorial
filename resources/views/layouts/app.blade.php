<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- CSRF für AJAX/FETCH/AXIOS --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Dynamischer Titel --}}
    <title>{{ $title ?? 'UschiWeb' }}</title>

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        /* ═══════════════════════════════════════════════════════════
           NAVIGATION - Schlicht Dunkel
           ═══════════════════════════════════════════════════════════ */
        .navbar-custom {
            background-color: #2d2d2d;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-custom .navbar-brand {
            font-weight: 600;
            color: #fff !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-custom .navbar-brand i {
            font-size: 1.3rem;
        }

        /* Nav Links */
        .navbar-custom .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            padding: 0.5rem 0.9rem !important;
            border-radius: 4px;
            margin: 0 2px;
            transition: all 0.15s ease;
        }

        .navbar-custom .nav-link:hover {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .navbar-custom .nav-link.active {
            color: #fff !important;
            background-color: #444;
        }

        /* Dropdown */
        .navbar-custom .dropdown-menu {
            background-color: #363636;
            border: 1px solid #444;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            padding: 0.4rem;
            min-width: 200px;
        }

        .navbar-custom .dropdown-item {
            color: rgba(255, 255, 255, 0.85);
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-custom .dropdown-item:hover {
            background-color: #444;
            color: #fff;
        }

        .navbar-custom .dropdown-item.active {
            background-color: #505050;
            color: #fff;
        }

        .navbar-custom .dropdown-item i {
            width: 18px;
            text-align: center;
            opacity: 0.7;
        }

        .navbar-custom .dropdown-divider {
            border-color: #444;
            margin: 0.3rem 0;
        }

        /* Badge */
        .nav-badge {
            background-color: #dc3545;
            color: #fff;
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            margin-left: 0.3rem;
            font-weight: 600;
        }

        .dropdown-badge {
            background-color: #ffc107;
            color: #000;
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            margin-left: auto;
            font-weight: 600;
        }

        /* User Area */
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .user-info i {
            font-size: 1.1rem;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: rgba(255, 255, 255, 0.8);
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.15s;
        }

        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            color: #fff;
        }

        /* Mobile */
        .navbar-custom .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .navbar-custom .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        @media (max-width: 991.98px) {
            .navbar-custom .navbar-collapse {
                padding-top: 0.75rem;
                border-top: 1px solid #444;
                margin-top: 0.75rem;
            }

            .navbar-custom .nav-link {
                padding: 0.6rem 0.75rem !important;
            }

            .navbar-custom .dropdown-menu {
                background-color: #3a3a3a;
                border: none;
                box-shadow: none;
                padding-left: 1rem;
            }

            .user-info {
                margin-top: 0.75rem;
                padding-top: 0.75rem;
                border-top: 1px solid #444;
            }
        }

        /* ═══════════════════════════════════════════════════════════
           CONTENT & FOOTER
           ═══════════════════════════════════════════════════════════ */
        .main-content {
            padding-bottom: 2rem;
        }

        footer {
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.85rem;
        }

        /* ═══════════════════════════════════════════════════════════
           SELECT2
           ═══════════════════════════════════════════════════════════ */
        .select2-container {
            width: 100% !important;
        }

        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #ced4da !important;
            border-radius: 0.375rem !important;
            background-color: #fff !important;
            height: calc(2.5rem + 2px) !important;
            padding: 0.375rem 0.75rem !important;
            display: flex !important;
            align-items: center !important;
            box-shadow: none !important;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .select2-container--bootstrap-5 .select2-selection:hover {
            border-color: #86b7fe !important;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .25) !important;
        }

        .select2-container--bootstrap-5 .select2-selection__arrow {
            top: 50% !important;
            transform: translateY(-50%) !important;
            right: 0.75rem !important;
        }

        .select2-container--bootstrap-5 .select2-search .select2-search__field {
            width: 100% !important;
            height: calc(2.25rem);
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }

        .select2-selection__clear {
            display: none !important;
        }
    </style>

    @stack('styles')

</head>

<body>

    {{-- NAVIGATION --}}
    <nav class="navbar navbar-expand-lg navbar-custom mb-4">
        <div class="container">
            {{-- Brand --}}
            <a class="navbar-brand" href="/">
                <i class="bi bi-building"></i>
                UschiWeb
            </a>

            {{-- Mobile Toggler --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    
                    {{-- Stammdaten --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('gebaeude*') || request()->is('adresse*') || request()->is('tour*') ? 'active' : '' }}" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-folder"></i> Stammdaten
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item {{ request()->is('gebaeude*') ? 'active' : '' }}" href="{{ url('/gebaeude') }}">
                                    <i class="bi bi-building"></i> Gebäude
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->is('adresse*') ? 'active' : '' }}" href="{{ url('/adresse') }}">
                                    <i class="bi bi-person-lines-fill"></i> Adressen
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->is('tour*') ? 'active' : '' }}" href="{{ url('/tour') }}">
                                    <i class="bi bi-signpost-split"></i> Touren
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Reinigungsplanung --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('reinigungsplanung*') ? 'active' : '' }}" 
                           href="{{ route('reinigungsplanung.index') }}">
                            <i class="bi bi-calendar-check"></i> Reinigung
                            @php
                                $offeneReinigungen = \App\Models\Gebaeude::where('faellig', true)->count();
                            @endphp
                            @if($offeneReinigungen > 0)
                                <span class="nav-badge">{{ $offeneReinigungen }}</span>
                            @endif
                        </a>
                    </li>

                    {{-- Finanzen --}}
                    <li class="nav-item dropdown">
                        @php
                            $offeneRechnungen = \App\Models\Gebaeude::where('rechnung_schreiben', true)->count();
                            $offeneBuchungen = \App\Models\BankBuchung::where('match_status', 'unmatched')->where('typ', 'CRDT')->count();
                            
                            // Überfällige Rechnungen für Mahnwesen (30 Tage Zahlungsfrist)
                            $ueberfaelligeRechnungen = 0;
                            try {
                                $ueberfaelligeRechnungen = \App\Models\Rechnung::where('status', 'sent')
                                    ->whereNotNull('rechnungsdatum')
                                    ->whereRaw("DATE_ADD(rechnungsdatum, INTERVAL 30 DAY) < CURDATE()")
                                    ->count();
                            } catch (\Exception $e) {
                                $ueberfaelligeRechnungen = 0;
                            }
                            
                            $finanzBadgeTotal = $offeneRechnungen + $offeneBuchungen + $ueberfaelligeRechnungen;
                        @endphp
                        <a class="nav-link dropdown-toggle {{ request()->is('rechnung*') || request()->is('preis-aufschlaege*') || request()->is('bank*') || request()->is('mahnungen*') ? 'active' : '' }}" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-wallet2"></i> Finanzen
                            @if($finanzBadgeTotal > 0)
                                <span class="nav-badge">{{ $finanzBadgeTotal }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item {{ request()->is('rechnung*') ? 'active' : '' }}" href="{{ url('/rechnung') }}">
                                    <i class="bi bi-file-text"></i> Rechnungen
                                    @if($offeneRechnungen > 0)
                                        <span class="dropdown-badge">{{ $offeneRechnungen }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->is('bank*') ? 'active' : '' }}" href="{{ route('bank.index') }}">
                                    <i class="bi bi-bank"></i> Bank-Buchungen
                                    @if($offeneBuchungen > 0)
                                        <span class="dropdown-badge bg-warning text-dark">{{ $offeneBuchungen }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->is('mahnungen*') ? 'active' : '' }}" href="{{ route('mahnungen.index') }}">
                                    <i class="bi bi-envelope-exclamation"></i> Mahnwesen
                                    @if($ueberfaelligeRechnungen > 0)
                                        <span class="dropdown-badge bg-danger text-white">{{ $ueberfaelligeRechnungen }}</span>
                                    @endif
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item {{ request()->is('preis-aufschlaege*') ? 'active' : '' }}" href="{{ route('preis-aufschlaege.index') }}">
                                    <i class="bi bi-percent"></i> Preis-Aufschläge
                                </a>
                            </li>
                        </ul>
                    </li>

                </ul>

                {{-- Benutzer --}}
                <div class="user-info">
                    @auth
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline ms-2">
                            @csrf
                            <button type="submit" class="btn-logout">
                                <i class="bi bi-box-arrow-right"></i>
                                <span class="d-none d-sm-inline">Logout</span>
                            </button>
                        </form>
                    @endauth

                    @guest
                        <a class="btn-logout" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    {{-- Hauptinhalt --}}
    <main class="container main-content">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer>
        <small>&copy; {{ date('Y') }} Resch GmbH Meisterbetrieb — UschiWeb</small>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- jQuery & Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

    {{-- Select2 Init --}}
    <script>
        function initSelect2(scope) {
            const $root = scope ? $(scope) : $(document);
            $root.find('select.js-select2').each(function() {
                const $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                const $modalParent = $el.closest('.modal');
                const dropdownParent = $modalParent.length ? $modalParent : $(document.body);
                $el.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: $el.data('placeholder') || 'Bitte wählen…',
                    allowClear: $el.data('allow-clear') !== 'false',
                    dropdownParent: dropdownParent,
                    language: {
                        noResults: () => 'Keine Treffer gefunden',
                        searching: () => 'Suche…'
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => initSelect2());
        document.addEventListener('shown.bs.tab', e => initSelect2(e.target));
        document.addEventListener('shown.bs.modal', e => initSelect2(e.target));
    </script>

    @stack('scripts')

</body>

</html>
