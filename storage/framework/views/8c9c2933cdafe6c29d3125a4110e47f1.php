
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    
    <title><?php echo e($title ?? 'UschiWeb'); ?></title>

    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>


    <style>
        body {
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        /* ═══════════════════════════════════════════════════════════
           NAVIGATION - Kompakt & Responsiv
           ═══════════════════════════════════════════════════════════ */
        .navbar-custom {
            background-color: #2d2d2d;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding-top: 0.4rem;
            padding-bottom: 0.4rem;
        }

        .navbar-custom .navbar-brand {
            font-weight: 600;
            color: #fff !important;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 1.1rem;
            padding: 0;
        }

        .navbar-custom .navbar-brand i {
            font-size: 1.2rem;
        }

        /* Nav Links - Kompakter */
        .navbar-custom .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.4rem 0.6rem !important;
            border-radius: 4px;
            margin: 0 1px;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .navbar-custom .nav-link:hover {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .navbar-custom .nav-link.active {
            color: #fff !important;
            background-color: #444;
        }

        /* Nav Icons */
        .navbar-custom .nav-link i {
            font-size: 0.9rem;
        }

        /* Dropdown */
        .navbar-custom .dropdown-menu {
            background-color: #363636;
            border: 1px solid #444;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            padding: 0.3rem;
            min-width: 180px;
            font-size: 0.85rem;
        }

        .navbar-custom .dropdown-item {
            color: rgba(255, 255, 255, 0.85);
            border-radius: 4px;
            padding: 0.4rem 0.65rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
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
            width: 16px;
            text-align: center;
            opacity: 0.7;
            font-size: 0.85rem;
        }

        .navbar-custom .dropdown-divider {
            border-color: #444;
            margin: 0.25rem 0;
        }

        /* Badge - Kompakter */
        .nav-badge {
            background-color: #dc3545;
            color: #fff;
            font-size: 0.65rem;
            padding: 0.1rem 0.35rem;
            border-radius: 8px;
            margin-left: 0.2rem;
            font-weight: 600;
            line-height: 1;
        }

        .dropdown-badge {
            background-color: #ffc107;
            color: #000;
            font-size: 0.65rem;
            padding: 0.1rem 0.35rem;
            border-radius: 8px;
            margin-left: auto;
            font-weight: 600;
        }

        /* User Area - Kompakter */
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.8rem;
        }

        .user-info i {
            font-size: 1rem;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: rgba(255, 255, 255, 0.8);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            transition: all 0.15s;
        }

        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            color: #fff;
        }

        /* ═══════════════════════════════════════════════════════════
           RESPONSIVE: Mittlere Bildschirme (Tablet/kleiner Desktop)
           ═══════════════════════════════════════════════════════════ */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .navbar-custom .nav-link {
                font-size: 0.8rem;
                padding: 0.35rem 0.45rem !important;
            }
            
            /* Text ausblenden, nur Icons zeigen */
            .navbar-custom .nav-text-hide-lg {
                display: none;
            }
            
            .navbar-custom .navbar-brand span {
                display: none;
            }
        }

        /* ═══════════════════════════════════════════════════════════
           MOBILE TOGGLER - Hamburger Icon
           ═══════════════════════════════════════════════════════════ */
        .navbar-toggler {
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
        }

        .navbar-toggler-icon {
            width: 1.2rem;
            height: 1.2rem;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* ═══════════════════════════════════════════════════════════
           MAIN CONTENT BEREICH
           ═══════════════════════════════════════════════════════════ */
        .main-content {
            padding-top: 1rem;
            padding-bottom: 2rem;
            min-height: calc(100vh - 120px);
        }

        /* ═══════════════════════════════════════════════════════════
           FOOTER
           ═══════════════════════════════════════════════════════════ */
        footer {
            background-color: #2d2d2d;
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
            padding: 1rem;
            margin-top: auto;
        }

        /* ═══════════════════════════════════════════════════════════
           HOVER-EFFEKTE FÜR CARDS
           ═══════════════════════════════════════════════════════════ */
        .hover-shadow {
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .hover-shadow:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        /* ═══════════════════════════════════════════════════════════
           SELECT2 ANPASSUNGEN
           ═══════════════════════════════════════════════════════════ */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            z-index: 9999;
        }

        /* ═══════════════════════════════════════════════════════════
           UTILITY KLASSEN
           ═══════════════════════════════════════════════════════════ */
        .cursor-pointer {
            cursor: pointer;
        }

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body class="d-flex flex-column">

    
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid px-3">
            
            <a class="navbar-brand" href="<?php echo e(url('/')); ?>">
                <i class="bi bi-building"></i>
                <span>UschiWeb</span>
            </a>

            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    
                    
                    <li class="nav-item dropdown">
                        <?php
                            // ⭐ NEU: Anzahl ausstehender Änderungsvorschläge
                            $ausstehendeVorschlaege = 0;
                            try {
                                $ausstehendeVorschlaege = \App\Models\GebaeudeAenderungsvorschlag::pending()->count();
                            } catch (\Exception $e) {}
                        ?>
                        
                        <a class="nav-link dropdown-toggle <?php echo e(request()->is('gebaeude*') || request()->is('adresse*') || request()->is('tour*') || request()->is('aenderungsvorschlaege*') ? 'active' : ''); ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-folder"></i> <span class="nav-text-hide-lg">Stamm</span><span class="d-none d-xl-inline">daten</span>
                            
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ausstehendeVorschlaege > 0): ?>
                                <span class="nav-badge"><?php echo e($ausstehendeVorschlaege); ?></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('gebaeude*') && !request()->is('*aenderungsvorschlaege*') ? 'active' : ''); ?>" href="<?php echo e(url('/gebaeude')); ?>">
                                    <i class="bi bi-building"></i> Gebäude
                                </a>
                            </li>
                            
                            
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('aenderungsvorschlaege*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.aenderungsvorschlaege')); ?>">
                                    <i class="bi bi-clipboard-check"></i> Änderungsvorschläge
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ausstehendeVorschlaege > 0): ?>
                                        <span class="dropdown-badge"><?php echo e($ausstehendeVorschlaege); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('adresse*') ? 'active' : ''); ?>" href="<?php echo e(url('/adresse')); ?>">
                                    <i class="bi bi-person-lines-fill"></i> Adressen
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('tour*') ? 'active' : ''); ?>" href="<?php echo e(url('/tour')); ?>">
                                    <i class="bi bi-signpost-split"></i> Touren
                                </a>
                            </li>
                        </ul>
                    </li>

                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->is('reinigungsplanung*') ? 'active' : ''); ?>" 
                           href="<?php echo e(route('reinigungsplanung.index')); ?>">
                            <i class="bi bi-calendar-check"></i> <span class="nav-text-hide-lg">Reinigung</span>
                            <?php
                                $offeneReinigungen = \App\Models\Gebaeude::where('faellig', true)->count();
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($offeneReinigungen > 0): ?>
                                <span class="nav-badge"><?php echo e($offeneReinigungen); ?></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                    </li>

                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->is('messungen*') ? 'active' : ''); ?>" 
                           href="<?php echo e(route('messungen.index')); ?>">
                            <i class="bi bi-rulers"></i> <span class="nav-text-hide-lg">Messungen</span>
                        </a>
                    </li>

                    
                    <li class="nav-item dropdown">
                        <?php
                            $offeneAngebote = 0;
                            try {
                                $offeneAngebote = \App\Models\Angebot::whereIn('status', ['entwurf', 'versendet'])->count();
                            } catch (\Exception $e) {}
                        ?>
                        <a class="nav-link dropdown-toggle <?php echo e(request()->routeIs('angebote.*') ? 'active' : ''); ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-file-earmark-text"></i> <span class="nav-text-hide-lg">Angebote</span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($offeneAngebote > 0): ?>
                                <span class="nav-badge" style="background-color: #17a2b8;"><?php echo e($offeneAngebote); ?></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?php echo e(route('angebote.index')); ?>">
                                    <i class="bi bi-list"></i> Übersicht
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo e(route('angebote.create')); ?>">
                                    <i class="bi bi-plus-lg"></i> Neues Angebot
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo e(route('angebote.index', ['status' => 'entwurf'])); ?>">
                                    <i class="bi bi-pencil"></i> Entwürfe
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo e(route('angebote.index', ['status' => 'versendet'])); ?>">
                                    <i class="bi bi-envelope"></i> Versendet
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo e(route('angebote.index', ['status' => 'angenommen'])); ?>">
                                    <i class="bi bi-check-circle text-success"></i> Angenommen
                                </a>
                            </li>
                        </ul>
                    </li>

                    
                    <li class="nav-item dropdown">
                        <?php
                            $offeneRechnungen = \App\Models\Gebaeude::where('rechnung_schreiben', true)->count();
                            $offeneBuchungen = \App\Models\BankBuchung::where('match_status', 'unmatched')->where('typ', 'CRDT')->count();
                            
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

                            $offeneEingangsrechnungen = 0;
                            try {
                                $offeneEingangsrechnungen = \App\Models\Eingangsrechnung::where('status', 'offen')->count();
                                $finanzBadgeTotal += $offeneEingangsrechnungen;
                            } catch (\Exception $e) {}
                        ?>
                        <a class="nav-link dropdown-toggle <?php echo e(request()->is('rechnung*') || request()->is('preis-aufschlaege*') || request()->is('bank*') || request()->is('mahnungen*') || request()->is('eingangsrechnungen*') ? 'active' : ''); ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-wallet2"></i> <span class="nav-text-hide-lg">Finanzen</span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($finanzBadgeTotal > 0): ?>
                                <span class="nav-badge"><?php echo e($finanzBadgeTotal); ?></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('rechnung*') ? 'active' : ''); ?>" href="<?php echo e(url('/rechnung')); ?>">
                                    <i class="bi bi-file-text"></i> Rechnungen
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($offeneRechnungen > 0): ?>
                                        <span class="dropdown-badge"><?php echo e($offeneRechnungen); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('eingangsrechnungen*') ? 'active' : ''); ?>" href="<?php echo e(route('eingangsrechnungen.index')); ?>">
                                    <i class="bi bi-receipt"></i> Eingangsrechnungen
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($offeneEingangsrechnungen > 0): ?>
                                        <span class="dropdown-badge bg-warning text-dark"><?php echo e($offeneEingangsrechnungen); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('bank*') ? 'active' : ''); ?>" href="<?php echo e(route('bank.index')); ?>">
                                    <i class="bi bi-bank"></i> Bank
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($offeneBuchungen > 0): ?>
                                        <span class="dropdown-badge bg-warning text-dark"><?php echo e($offeneBuchungen); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('mahnungen*') ? 'active' : ''); ?>" href="<?php echo e(route('mahnungen.index')); ?>">
                                    <i class="bi bi-envelope-exclamation"></i> Mahnwesen
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ueberfaelligeRechnungen > 0): ?>
                                        <span class="dropdown-badge bg-danger text-white"><?php echo e($ueberfaelligeRechnungen); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('preis-aufschlaege*') ? 'active' : ''); ?>" href="<?php echo e(route('preis-aufschlaege.index')); ?>">
                                    <i class="bi bi-percent"></i> Aufschläge
                                </a>
                            </li>
                        </ul>
                    </li>

                    
                    <li class="nav-item dropdown">
                        <?php
                            $neueLohnstunden = 0;
                            try {
                                $neueLohnstunden = \App\Models\Lohnstunde::whereDate('created_at', '>=', now()->subDays(7))->count();
                            } catch (\Exception $e) {}
                        ?>
                        <a class="nav-link dropdown-toggle <?php echo e(request()->is('lohnstunden*') ? 'active' : ''); ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-people"></i> <span class="nav-text-hide-lg">Personal</span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($neueLohnstunden > 0): ?>
                                <span class="nav-badge" style="background-color: #17a2b8;"><?php echo e($neueLohnstunden); ?></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?php echo e(request()->is('lohnstunden*') ? 'active' : ''); ?>" 
                                   href="<?php echo e(route('admin.lohnstunden')); ?>">
                                    <i class="bi bi-clock-history"></i> Lohnstunden
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($neueLohnstunden > 0): ?>
                                        <span class="dropdown-badge bg-info text-white"><?php echo e($neueLohnstunden); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </li>

                </ul>

                
                <div class="user-info">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-xl-inline"><?php echo e(Auth::user()->name); ?></span>
                        <form method="POST" action="<?php echo e(route('logout')); ?>" class="d-inline ms-1">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn-logout">
                                <i class="bi bi-box-arrow-right"></i>
                                <span class="d-none d-sm-inline">Logout</span>
                            </button>
                        </form>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->guest()): ?>
                        <a class="btn-logout" href="<?php echo e(route('login')); ?>">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    
    <main class="container-fluid px-3 px-md-4 main-content mt-3">
        <?php if (! empty(trim($__env->yieldContent('content')))): ?>
            <?php echo $__env->yieldContent('content'); ?>
        <?php else: ?>
            <?php echo e($slot ?? ''); ?>

        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </main>

    
    <?php echo $__env->yieldPushContent('modals'); ?>

    
    <footer>
        <small>&copy; <?php echo e(date('Y')); ?> Resch GmbH — UschiWeb</small>
    </footer>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

    
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>


    
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

    <?php echo $__env->yieldPushContent('scripts'); ?>

</body>

</html>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/layouts/app.blade.php ENDPATH**/ ?>