<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e5631">

    <title>{{ $title ?? 'Mitarbeiter' }} – {{ config('app.name', 'UschiWeb') }}</title>

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Livewire Styles --}}
    @livewireStyles

    <style>
        :root {
            --nav-height: 56px;
        }

        body {
            background-color: #f5f5f5;
            min-height: 100vh;
            padding-bottom: env(safe-area-inset-bottom);
        }

        /* Navigation - Mitarbeiter (Grün) */
        .navbar-mitarbeiter {
            background-color: #1e5631;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            padding: 0.5rem 0;
        }

        .navbar-mitarbeiter .navbar-brand {
            font-weight: 600;
            color: #fff !important;
            font-size: 1.1rem;
        }

        .navbar-mitarbeiter .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.5rem 0.75rem !important;
            border-radius: 4px;
            font-size: 0.95rem;
        }

        .navbar-mitarbeiter .nav-link:hover,
        .navbar-mitarbeiter .nav-link.active {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .navbar-mitarbeiter .navbar-toggler {
            border: none;
            padding: 0.25rem 0.5rem;
        }

        .navbar-mitarbeiter .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-mitarbeiter .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* User Info in Nav */
        .user-badge {
            background: rgba(255,255,255,0.15);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.9);
        }

        .btn-logout {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: rgba(255, 255, 255, 0.9);
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        /* Main Content */
        .main-content {
            padding-top: 1.5rem;
            padding-bottom: 2rem;
        }

        /* Footer */
        footer {
            background-color: #e9ecef;
            padding: 0.75rem;
            text-align: center;
            color: #6c757d;
            font-size: 0.8rem;
        }

        /* Cards */
        .stat-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .stat-card .stat-icon {
            font-size: 1.5rem;
            opacity: 0.8;
        }

        .stat-card .stat-value {
            font-weight: 700;
        }

        .stat-card .stat-label {
            color: #6c757d;
        }

        /* Alert anpassungen */
        .alert {
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        /* Mobile Optimierungen */
        @media (max-width: 767.98px) {
            .main-content {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .card {
                border-radius: 0.5rem;
            }

            .card-header, .card-footer {
                padding: 0.5rem 0.75rem;
            }

            .card-body {
                padding: 0.75rem;
            }

            .table {
                font-size: 0.9rem;
            }

            h2 {
                font-size: 1.25rem;
            }

            .navbar-mitarbeiter .navbar-collapse {
                padding: 0.75rem 0;
                margin-top: 0.5rem;
                border-top: 1px solid rgba(255,255,255,0.1);
            }

            .navbar-mitarbeiter .nav-link {
                padding: 0.75rem 1rem !important;
            }
        }

        /* Touch-Optimierung */
        @media (pointer: coarse) {
            .btn {
                min-height: 44px;
            }
            
            .form-control, .form-select {
                min-height: 44px;
                font-size: 16px; /* Verhindert Zoom auf iOS */
            }

            .btn-sm {
                min-height: 38px;
                min-width: 38px;
            }
        }

        /* Safe Area für iPhone Notch */
        @supports (padding: max(0px)) {
            .navbar-mitarbeiter {
                padding-top: max(0.5rem, env(safe-area-inset-top));
            }
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    {{-- Navigation --}}
    <nav class="navbar navbar-expand-md navbar-mitarbeiter sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('mitarbeiter.dashboard') }}">
                <i class="bi bi-person-badge me-1"></i>
                <span class="d-none d-sm-inline">UschiWeb</span>
                <span class="d-sm-none">UW</span>
            </a>

            {{-- Mobile: User + Toggler --}}
            <div class="d-flex align-items-center gap-2 d-md-none">
                <span class="user-badge">
                    <i class="bi bi-person"></i> {{ Str::limit(Auth::user()->name, 10) }}
                </span>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('mitarbeiter.dashboard') ? 'active' : '' }}" 
                           href="{{ route('mitarbeiter.dashboard') }}">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('mitarbeiter.lohnstunden') ? 'active' : '' }}" 
                           href="{{ route('mitarbeiter.lohnstunden') }}">
                            <i class="bi bi-clock-history"></i> Stunden / Ore
                        </a>
                    </li>
                </ul>

                {{-- Desktop: User Info --}}
                <div class="d-none d-md-flex align-items-center gap-2">
                    <span class="user-badge">
                        <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>

                {{-- Mobile: Logout --}}
                <div class="d-md-none border-top border-light border-opacity-25 mt-2 pt-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-light w-100">
                            <i class="bi bi-box-arrow-right"></i> Logout / Esci
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if (session('success') || session('error'))
        <div class="container mt-3">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
    @endif

    {{-- Hauptinhalt / Contenuto principale --}}
    <main class="container main-content flex-grow-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer>
        <small>&copy; {{ date('Y') }} Resch GmbH</small>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Livewire Scripts --}}
    @livewireScripts

</body>

</html>
