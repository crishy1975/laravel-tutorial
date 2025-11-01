{{-- resources/views/components/navbar.blade.php --}}
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        {{-- Firmenname / Logo --}}
        <a class="navbar-brand fw-bold" href="/">
            <i class="bi bi-fire me-2 text-warning"></i>
            UschiWeb
        </a>

        {{-- Toggle-Button für Handy --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Navigationslinks --}}
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">

                {{-- Willkommen --}}
                <li class="nav-item">
                    <a class="nav-link {{ $active === 'willkommen' ? 'active fw-bold' : '' }}" href="/willkommen">
                        <i class="bi bi-house-door"></i> Willkommen
                    </a>
                </li>

                {{-- Dashboard --}}
                <li class="nav-item">
                    <a class="nav-link {{ $active === 'dashboard' ? 'active fw-bold' : '' }}" href="/dashboard">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>

                @guest
                <li class="nav-item">
                    <a class="nav-link {{ $active === 'login' ? 'active fw-bold' : '' }}" href="/login">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                </li>
                @endguest

                {{-- Logout (nur wenn eingeloggt) --}}
                @auth
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-link nav-link text-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </li>
                @endauth

            </ul>
        </div>
    </div>
</nav>