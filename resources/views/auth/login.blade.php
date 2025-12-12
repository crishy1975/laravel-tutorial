<x-guest-layout>
    
    {{-- Status-Meldung (z.B. nach Passwort-Reset) --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Fehler-Meldungen --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- E-Mail --}}
        <div class="form-floating mb-3">
            <input 
                type="email" 
                class="form-control @error('email') is-invalid @enderror" 
                id="email" 
                name="email" 
                value="{{ old('email') }}" 
                placeholder="name@example.com" 
                required 
                autofocus 
                autocomplete="username"
            >
            <label for="email">
                <i class="bi bi-envelope me-1"></i> E-Mail-Adresse
            </label>
        </div>

        {{-- Passwort --}}
        <div class="form-floating mb-3">
            <input 
                type="password" 
                class="form-control @error('password') is-invalid @enderror" 
                id="password" 
                name="password" 
                placeholder="Passwort" 
                required 
                autocomplete="current-password"
            >
            <label for="password">
                <i class="bi bi-lock me-1"></i> Passwort
            </label>
        </div>

        {{-- Angemeldet bleiben --}}
        <div class="form-check mb-4">
            <input 
                class="form-check-input" 
                type="checkbox" 
                id="remember" 
                name="remember"
                {{ old('remember') ? 'checked' : '' }}
            >
            <label class="form-check-label" for="remember">
                Angemeldet bleiben
            </label>
        </div>

        {{-- Login Button --}}
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> Anmelden
            </button>
        </div>

        {{-- Passwort vergessen (optional) --}}
        @if (Route::has('password.request'))
            <div class="text-center">
                <a href="{{ route('password.request') }}" class="text-decoration-none text-muted small">
                    <i class="bi bi-question-circle me-1"></i> Passwort vergessen?
                </a>
            </div>
        @endif

    </form>

</x-guest-layout>