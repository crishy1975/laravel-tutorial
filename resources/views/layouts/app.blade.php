<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- ✅ CSRF für AJAX/FETCH/AXIOS --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Dynamischer Titel --}}
    <title>{{ $title ?? 'UschiWeb' }}</title>

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    {{-- Optional: eigenes CSS --}}
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar-brand {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        footer {
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
            color: #6c757d;
        }

        .select2-container {
            width: 100% !important;
        }

        /* Select2 exakt wie Bootstrap .form-select */
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

        /* Kein „x“ zum Leeren anzeigen */
        .select2-selection__clear {
            display: none !important;
        }
    </style>

</head>

<body>

    {{-- 🔹 Navigation --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-house-door"></i> UschiWeb
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('gebaeude*') ? 'active fw-bold' : '' }}" href="{{ url('/gebaeude') }}">
                            <i class="bi bi-building"></i> Gebäude
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('adresse*') ? 'active fw-bold' : '' }}" href="{{ url('/adresse') }}">
                            <i class="bi bi-person-lines-fill"></i> Adressen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('tour*') ? 'active fw-bold' : '' }}" href="{{ url('/tour') }}">
                            <i class="bi bi-map"></i> Touren
                        </a>
                    </li>


                </ul>

                {{-- 🔹 Benutzerbereich (rechts) --}}
                <ul class="navbar-nav ms-auto">
                    @auth
                    <li class="nav-item d-flex align-items-center me-2">
                        <i class="bi bi-person-circle text-light me-1"></i>
                        <span class="text-light small">{{ Auth::user()->name }}</span>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-light btn-sm">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                    @endauth

                    @guest
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    {{-- 🔹 Hauptinhalt --}}
    <main class="container">
        @yield('content')
    </main>

    {{-- 🔹 Footer --}}
    <footer>
        <small>&copy; {{ date('Y') }} Resch GmbH Meisterbetrieb – UschiWeb</small>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- jQuery & Select2 müssen VOR den Scripts der Seiten kommen --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

    {{-- 🔹 Globale Select2-Initialisierung --}}
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

    {{-- 🔹 Jetzt die Seitenskripte (z. B. updateEditLink) --}}
    @stack('scripts')

</body>

</html>