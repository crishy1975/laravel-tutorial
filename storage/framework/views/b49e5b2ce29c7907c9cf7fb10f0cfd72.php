<?php $__env->startSection('content'); ?>
<?php
    $year = now()->year;
    $defaultDatumVon = $datumVon ?? \Illuminate\Support\Carbon::create($year, 1, 1)->format('Y-m-d');
    $defaultDatumBis = $datumBis ?? \Illuminate\Support\Carbon::create($year, 12, 31)->format('Y-m-d');
?>

<div class="container-fluid py-3">

    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-receipt"></i> Rechnungen</h4>
    </div>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($stats)): ?>
    <div class="row g-2 g-md-3 mb-3">
        
        <div class="col-6 col-md-3">
            <div class="card border-0 bg-primary text-white h-100">
                <div class="card-body py-2 py-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-white-50 small">Umsatz <?php echo e($stats['aktuelles_jahr'] ?? now()->year); ?></div>
                            <div class="fs-5 fs-md-4 fw-bold"><?php echo e(number_format($stats['umsatz_aktuell'] ?? 0, 0, ',', '.')); ?> €</div>
                            <div class="small">
                                <span class="text-white-50"><?php echo e($stats['anzahl_aktuell'] ?? 0); ?> Rechnungen</span>
                            </div>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-3 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-6 col-md-3">
            <div class="card border-0 bg-secondary text-white h-100">
                <div class="card-body py-2 py-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-white-50 small">Umsatz <?php echo e($stats['vorjahr'] ?? (now()->year - 1)); ?></div>
                            <div class="fs-5 fs-md-4 fw-bold"><?php echo e(number_format($stats['umsatz_vorjahr'] ?? 0, 0, ',', '.')); ?> €</div>
                            <div class="small">
                                <?php
                                    $diff = ($stats['umsatz_aktuell'] ?? 0) - ($stats['umsatz_vorjahr'] ?? 0);
                                    $prozent = ($stats['umsatz_vorjahr'] ?? 0) > 0 
                                        ? (($diff / ($stats['umsatz_vorjahr'] ?? 1)) * 100) 
                                        : 0;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($diff >= 0): ?>
                                    <span class="text-success"><i class="bi bi-arrow-up"></i> +<?php echo e(number_format($prozent, 1, ',', '.')); ?>%</span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="bi bi-arrow-down"></i> <?php echo e(number_format($prozent, 1, ',', '.')); ?>%</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <i class="bi bi-calendar-check fs-3 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-6 col-md-3">
            <div class="card border-0 <?php echo e(($stats['unbezahlt_anzahl'] ?? 0) > 0 ? 'bg-warning' : 'bg-success'); ?> h-100">
                <div class="card-body py-2 py-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-dark small opacity-75">Offen / Unbezahlt</div>
                            <div class="fs-5 fs-md-4 fw-bold text-dark"><?php echo e(number_format($stats['unbezahlt_summe'] ?? 0, 0, ',', '.')); ?> €</div>
                            <div class="small text-dark">
                                <a href="<?php echo e(route('rechnung.index', ['status_filter' => 'unbezahlt'])); ?>" class="text-dark">
                                    <?php echo e($stats['unbezahlt_anzahl'] ?? 0); ?> Rechnungen <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <i class="bi bi-clock-history fs-3 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-6 col-md-3">
            <div class="card border-0 <?php echo e(($stats['ueberfaellig_anzahl'] ?? 0) > 0 ? 'bg-danger text-white' : 'bg-light'); ?> h-100">
                <div class="card-body py-2 py-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small <?php echo e(($stats['ueberfaellig_anzahl'] ?? 0) > 0 ? 'text-white-50' : 'text-muted'); ?>">Überfällig</div>
                            <div class="fs-5 fs-md-4 fw-bold"><?php echo e(number_format($stats['ueberfaellig_summe'] ?? 0, 0, ',', '.')); ?> €</div>
                            <div class="small">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($stats['ueberfaellig_anzahl'] ?? 0) > 0): ?>
                                    <a href="<?php echo e(route('rechnung.index', ['status_filter' => 'ueberfaellig'])); ?>" class="text-white">
                                        <?php echo e($stats['ueberfaellig_anzahl']); ?> Rechnungen <i class="bi bi-exclamation-triangle"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Keine</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <i class="bi bi-exclamation-diamond fs-3 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card mb-3 d-none d-lg-block">
        <div class="card-header py-2 bg-light">
            <i class="bi bi-bar-chart"></i> <strong>Jahresvergleich</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Jahr</th>
                        <th class="text-center">Anzahl</th>
                        <th class="text-end">Brutto</th>
                        <th class="text-end text-danger">Gutschriften</th>
                        <th class="text-end">Umsatz</th>
                        <th class="text-end text-success">Bezahlt</th>
                        <th class="text-end text-warning">Offen</th>
                        <th class="text-center">+/−</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [$stats['aktuelles_jahr'], $stats['vorjahr'], $stats['vorvorjahr']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $statJahr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $jahresStats = $stats['jahresvergleich'][$statJahr] ?? [];
                        $vorjahresStats = $stats['jahresvergleich'][$statJahr - 1] ?? [];
                        // Veränderung basierend auf UMSATZ (nicht Brutto)
                        $veraenderung = ($vorjahresStats['umsatz'] ?? 0) > 0 
                            ? ((($jahresStats['umsatz'] ?? 0) - ($vorjahresStats['umsatz'] ?? 0)) / ($vorjahresStats['umsatz'] ?? 1)) * 100
                            : 0;
                    ?>
                    <tr class="<?php echo e($i === 0 ? 'table-primary' : ''); ?>">
                        <td class="fw-bold"><?php echo e($statJahr); ?></td>
                        <td class="text-center"><?php echo e($jahresStats['anzahl'] ?? 0); ?></td>
                        <td class="text-end"><?php echo e(number_format($jahresStats['brutto'] ?? 0, 2, ',', '.')); ?> €</td>
                        <td class="text-end text-danger">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($jahresStats['gutschriften'] ?? 0) > 0): ?>
                                −<?php echo e(number_format($jahresStats['gutschriften'] ?? 0, 2, ',', '.')); ?> €
                            <?php else: ?>
                                –
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold"><?php echo e(number_format($jahresStats['umsatz'] ?? 0, 2, ',', '.')); ?> €</td>
                        <td class="text-end text-success"><?php echo e(number_format($jahresStats['bezahlt'] ?? 0, 2, ',', '.')); ?> €</td>
                        <td class="text-end <?php echo e(($jahresStats['offen'] ?? 0) > 0 ? 'text-warning fw-semibold' : ''); ?>">
                            <?php echo e(number_format($jahresStats['offen'] ?? 0, 2, ',', '.')); ?> €
                        </td>
                        <td class="text-center">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($i > 0 || $veraenderung != 0): ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($veraenderung >= 0): ?>
                                    <span class="badge bg-success"><i class="bi bi-arrow-up"></i> +<?php echo e(number_format($veraenderung, 1)); ?>%</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-arrow-down"></i> <?php echo e(number_format($veraenderung, 1)); ?>%</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">–</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="card mb-3">
        <div class="card-body py-2">
            
            <div class="row g-2">
                <div class="col-6 col-md-2">
                    <div class="form-floating">
                        <input type="text"
                            class="form-control form-control-sm"
                            id="filter-nummer"
                            name="nummer"
                            value="<?php echo e($nummer ?? ''); ?>"
                            placeholder="Nr.">
                        <label for="filter-nummer">Nummer</label>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="form-floating">
                        <input type="text"
                            class="form-control form-control-sm"
                            id="filter-codex"
                            name="codex"
                            value="<?php echo e($codex ?? ''); ?>"
                            placeholder="Codex">
                        <label for="filter-codex">Codex</label>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="text"
                            class="form-control form-control-sm"
                            id="filter-suche"
                            name="suche"
                            value="<?php echo e($suche ?? ''); ?>"
                            placeholder="Suche">
                        <label for="filter-suche">Suche (Gebäude, Empfänger, E-Mail)</label>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select form-select-sm" id="filter-status" name="status_filter">
                            <option value="">Alle Status</option>
                            <option value="unbezahlt" <?php echo e(($statusFilter ?? '') === 'unbezahlt' ? 'selected' : ''); ?>>Unbezahlt</option>
                            <option value="bezahlt" <?php echo e(($statusFilter ?? '') === 'bezahlt' ? 'selected' : ''); ?>>Bezahlt</option>
                            <option value="ueberfaellig" <?php echo e(($statusFilter ?? '') === 'ueberfaellig' ? 'selected' : ''); ?>>Überfällig</option>
                            <option value="bald_faellig" <?php echo e(($statusFilter ?? '') === 'bald_faellig' ? 'selected' : ''); ?>>Bald fällig (7 Tage)</option>
                            <option value="offen" <?php echo e(($statusFilter ?? '') === 'offen' ? 'selected' : ''); ?>>Offen (versendet)</option>
                        </select>
                        <label for="filter-status">Status</label>
                    </div>
                </div>
            </div>

            
            <div class="row g-2 mt-1">
                <div class="col-5 col-md-3">
                    <div class="form-floating">
                        <input type="date"
                            class="form-control form-control-sm"
                            id="filter-datum-von"
                            name="datum_von"
                            value="<?php echo e($defaultDatumVon); ?>">
                        <label for="filter-datum-von">Von</label>
                    </div>
                </div>
                <div class="col-5 col-md-3">
                    <div class="form-floating">
                        <input type="date"
                            class="form-control form-control-sm"
                            id="filter-datum-bis"
                            name="datum_bis"
                            value="<?php echo e($defaultDatumBis); ?>">
                        <label for="filter-datum-bis">Bis</label>
                    </div>
                </div>
                <div class="col-2 col-md-6 d-flex align-items-center justify-content-end gap-2">
                    <button type="button"
                        class="btn btn-outline-secondary"
                        id="btnResetFilter"
                        title="Filter zurücksetzen">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <button type="button"
                        class="btn btn-primary"
                        id="btnFilterRechnungen"
                        title="Filtern">
                        <i class="bi bi-funnel"></i>
                        <span class="d-none d-md-inline ms-1">Filtern</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnungen->isEmpty()): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Keine Rechnungen gefunden.
        </div>
    <?php else: ?>

        
        
        
        <div class="card d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="selectAllDesktop" title="Alle auswählen">
                            </th>
                            <th>Nummer ↓</th>
                            <th>Typ</th>
                            <th>Datum</th>
                            <th>Codex</th>
                            <th>Gebäude</th>
                            <th class="text-end">Zahlbar</th>
                            <th>Status</th>
                            <th class="text-center" style="width: 100px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rechnungen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rechnung): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="text-center">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->hat_xml): ?>
                                <input type="checkbox" class="form-check-input xml-checkbox" value="<?php echo e($rechnung->id); ?>" data-nummer="<?php echo e($rechnung->rechnungsnummer); ?>">
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-semibold font-monospace">
                                    <?php echo e($rechnung->rechnungsnummer); ?>

                                </span>
                            </td>
                            <td>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->typ_rechnung === 'gutschrift'): ?>
                                    <span class="badge bg-danger">GS</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">RE</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td><?php echo e(optional($rechnung->rechnungsdatum)->format('d.m.Y')); ?></td>
                            <td>
                                <code class="text-muted"><?php echo e($rechnung->geb_codex ?? $rechnung->gebaeude?->codex ?? '–'); ?></code>
                            </td>
                            <td>
                                <?php
                                    $gebName = $rechnung->geb_name ?? $rechnung->gebaeude?->gebaeude_name ?? '–';
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->gebaeude_id && $gebName !== '–'): ?>
                                    <a href="<?php echo e(route('gebaeude.edit', $rechnung->gebaeude_id)); ?>"
                                       class="text-decoration-none">
                                        <?php echo e(Str::limit($gebName, 30)); ?>

                                    </a>
                                <?php else: ?>
                                    <?php echo e(Str::limit($gebName, 30)); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php
                                    $zahlbar = $rechnung->zahlbetrag ?? $rechnung->brutto_summe ?? 0;
                                    if ($rechnung->typ_rechnung === 'gutschrift') {
                                        $zahlbar = -abs($zahlbar);
                                    }
                                ?>
                                <span class="fw-semibold <?php echo e($zahlbar < 0 ? 'text-danger' : ''); ?>">
                                    <?php echo e(number_format($zahlbar, 2, ',', '.')); ?> €
                                </span>
                            </td>
                            <td>
                                <?php
                                    $status = $rechnung->status ?? 'draft';
                                    $badgeClass = match ($status) {
                                        'paid' => 'bg-success',
                                        'cancelled' => 'bg-secondary',
                                        'draft' => 'bg-warning text-dark',
                                        'sent' => 'bg-primary',
                                        'overdue' => 'bg-danger',
                                        default => 'bg-light text-dark',
                                    };
                                ?>
                                <span class="badge <?php echo e($badgeClass); ?>"><?php echo e(ucfirst($status)); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->istUeberfaellig()): ?>
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo e(route('rechnung.edit', $rechnung->id)); ?>"
                                       class="btn btn-outline-primary"
                                       title="Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->hat_xml): ?>
                                    <a href="<?php echo e(route('rechnung.xml.download', $rechnung->id)); ?>"
                                       class="btn btn-outline-secondary"
                                       title="XML">
                                        <i class="bi bi-file-earmark-code"></i>
                                    </a>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center py-2">
                <small class="text-muted">
                    <?php echo e($rechnungen->total()); ?> Rechnungen
                </small>
                <div>
                    <?php echo e($rechnungen->appends([
                        'nummer'        => $nummer ?? null,
                        'codex'         => $codex ?? null,
                        'suche'         => $suche ?? null,
                        'datum_von'     => $datumVon ?? $defaultDatumVon,
                        'datum_bis'     => $datumBis ?? $defaultDatumBis,
                        'status_filter' => $statusFilter ?? null,
                    ])->links()); ?>

                </div>
            </div>
        </div>

        
        
        
        <div class="d-md-none">
            
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="text-muted"><?php echo e($rechnungen->total()); ?> Rechnungen</small>
                <small class="text-muted">Sortiert nach Nr. ↓</small>
            </div>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rechnungen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rechnung): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $gebName = $rechnung->geb_name ?? $rechnung->gebaeude?->gebaeude_name ?? '–';
                    $rCodex = $rechnung->geb_codex ?? $rechnung->gebaeude?->codex ?? '–';
                    $zahlbar = $rechnung->zahlbetrag ?? $rechnung->brutto_summe ?? 0;
                    if ($rechnung->typ_rechnung === 'gutschrift') {
                        $zahlbar = -abs($zahlbar);
                    }
                    $status = $rechnung->status ?? 'draft';
                    $badgeClass = match ($status) {
                        'paid' => 'bg-success',
                        'cancelled' => 'bg-secondary',
                        'draft' => 'bg-warning text-dark',
                        'sent' => 'bg-primary',
                        'overdue' => 'bg-danger',
                        default => 'bg-light text-dark',
                    };
                ?>

                <div class="card mb-2 <?php echo e($rechnung->typ_rechnung === 'gutschrift' ? 'border-danger' : ''); ?> <?php echo e($rechnung->istUeberfaellig() ? 'border-warning border-2' : ''); ?>">
                    <div class="card-body py-2 px-3">
                        
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->hat_xml): ?>
                                <input type="checkbox" class="form-check-input xml-checkbox" value="<?php echo e($rechnung->id); ?>" data-nummer="<?php echo e($rechnung->rechnungsnummer); ?>">
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <span class="fw-bold font-monospace"><?php echo e($rechnung->rechnungsnummer); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->typ_rechnung === 'gutschrift'): ?>
                                    <span class="badge bg-danger">GS</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">RE</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <span class="badge <?php echo e($badgeClass); ?>"><?php echo e(ucfirst($status)); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->istUeberfaellig()): ?>
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo e(route('rechnung.edit', $rechnung->id)); ?>"
                                   class="btn btn-outline-primary"
                                   title="Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->hat_xml): ?>
                                <a href="<?php echo e(route('rechnung.xml.download', $rechnung->id)); ?>"
                                   class="btn btn-outline-secondary"
                                   title="XML">
                                    <i class="bi bi-file-earmark-code"></i>
                                </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        
                        <div class="d-flex justify-content-between text-muted small mb-1">
                            <span>
                                <i class="bi bi-calendar3"></i>
                                <?php echo e(optional($rechnung->rechnungsdatum)->format('d.m.Y')); ?>

                            </span>
                            <code><?php echo e($rCodex); ?></code>
                        </div>

                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-truncate me-2" style="max-width: 60%;">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->gebaeude_id && $gebName !== '–'): ?>
                                    <a href="<?php echo e(route('gebaeude.edit', $rechnung->gebaeude_id)); ?>"
                                       class="text-decoration-none">
                                        <?php echo e(Str::limit($gebName, 25)); ?>

                                    </a>
                                <?php else: ?>
                                    <?php echo e(Str::limit($gebName, 25)); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                            <span class="fw-bold <?php echo e($zahlbar < 0 ? 'text-danger' : 'text-success'); ?>">
                                <?php echo e(number_format($zahlbar, 2, ',', '.')); ?> €
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <div class="d-flex justify-content-center mt-3">
                <?php echo e($rechnungen->appends([
                    'nummer'        => $nummer ?? null,
                    'codex'         => $codex ?? null,
                    'suche'         => $suche ?? null,
                    'datum_von'     => $datumVon ?? $defaultDatumVon,
                    'datum_bis'     => $datumBis ?? $defaultDatumBis,
                    'status_filter' => $statusFilter ?? null,
                ])->links()); ?>

            </div>
        </div>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <div id="bulkActionBar" class="position-fixed bottom-0 start-50 translate-middle-x mb-3 d-none" style="z-index: 1050;">
        <div class="card shadow-lg border-primary">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
                <span class="badge bg-primary fs-6" id="selectedCount">0</span>
                <span class="text-nowrap">Rechnungen ausgewählt</span>
                <button type="button" class="btn btn-success btn-sm" id="btnBulkXmlDownload" title="Ausgewählte XMLs herunterladen">
                    <i class="bi bi-file-earmark-code"></i>
                    <span class="d-none d-sm-inline ms-1">XML herunterladen</span>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearSelection" title="Auswahl aufheben">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseIndexUrl = "<?php echo e(route('rechnung.index')); ?>";
    const COOKIE_NAME = 'rechnung_filter';
    const COOKIE_DAYS = 30;

    const nummerInput = document.getElementById('filter-nummer');
    const codexInput = document.getElementById('filter-codex');
    const sucheInput = document.getElementById('filter-suche');
    const datumVonInput = document.getElementById('filter-datum-von');
    const datumBisInput = document.getElementById('filter-datum-bis');
    const statusInput = document.getElementById('filter-status');
    const btnFilter = document.getElementById('btnFilterRechnungen');
    const btnReset = document.getElementById('btnResetFilter');

    // ═══════════════════════════════════════════════════════════════════════════════
    // Cookie Funktionen
    // ═══════════════════════════════════════════════════════════════════════════════
    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + encodeURIComponent(JSON.stringify(value)) + 
                          ';expires=' + expires.toUTCString() + 
                          ';path=/;SameSite=Lax';
    }

    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) {
                try {
                    return JSON.parse(decodeURIComponent(c.substring(nameEQ.length)));
                } catch (e) {
                    return null;
                }
            }
        }
        return null;
    }

    function deleteCookie(name) {
        document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Aktuelle Page aus URL lesen
    // ═══════════════════════════════════════════════════════════════════════════════
    function getCurrentPage() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('page') || '1';
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Prüfen ob URL Parameter hat (außer page)
    // ═══════════════════════════════════════════════════════════════════════════════
    function hasUrlFilterParams() {
        const urlParams = new URLSearchParams(window.location.search);
        // Prüfe ob irgendein Filter-Parameter vorhanden ist
        return urlParams.has('nummer') || 
               urlParams.has('codex') || 
               urlParams.has('suche') || 
               urlParams.has('datum_von') || 
               urlParams.has('datum_bis') || 
               urlParams.has('status_filter') ||
               urlParams.has('page');
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Filter + Page + Scroll in Cookie speichern
    // ═══════════════════════════════════════════════════════════════════════════════
    function saveFilterToCookie() {
        const filterData = {
            nummer: nummerInput?.value || '',
            codex: codexInput?.value || '',
            suche: sucheInput?.value || '',
            datum_von: datumVonInput?.value || '',
            datum_bis: datumBisInput?.value || '',
            status_filter: statusInput?.value || '',
            page: getCurrentPage(),
            scrollY: window.scrollY || 0
        };
        setCookie(COOKIE_NAME, filterData, COOKIE_DAYS);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Bei Seitenaufruf ohne Parameter: Aus Cookie wiederherstellen
    // ═══════════════════════════════════════════════════════════════════════════════
    function restoreFromCookieIfNeeded() {
        // Nur wiederherstellen wenn KEINE URL-Parameter vorhanden
        if (hasUrlFilterParams()) {
            // URL hat Parameter -> speichere aktuelle Position
            saveFilterToCookie();
            return;
        }

        const filterData = getCookie(COOKIE_NAME);
        if (!filterData) return;

        // Prüfen ob es gespeicherte Filter gibt
        const hasFilter = filterData.nummer || 
                         filterData.codex || 
                         filterData.suche || 
                         filterData.status_filter ||
                         (filterData.page && filterData.page !== '1');

        if (!hasFilter) return;

        // URL mit gespeicherten Filtern aufbauen
        const params = new URLSearchParams();

        if (filterData.nummer) params.append('nummer', filterData.nummer);
        if (filterData.codex) params.append('codex', filterData.codex);
        if (filterData.suche) params.append('suche', filterData.suche);
        if (filterData.datum_von) params.append('datum_von', filterData.datum_von);
        if (filterData.datum_bis) params.append('datum_bis', filterData.datum_bis);
        if (filterData.status_filter) params.append('status_filter', filterData.status_filter);
        if (filterData.page && filterData.page !== '1') params.append('page', filterData.page);

        // Redirect zur gespeicherten Position
        if (params.toString()) {
            window.location.href = baseIndexUrl + '?' + params.toString();
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Filter aus Cookie in Felder laden (für manuelle Ansicht)
    // ═══════════════════════════════════════════════════════════════════════════════
    function loadFilterFromCookie() {
        const filterData = getCookie(COOKIE_NAME);
        if (!filterData) return;

        // Felder mit Cookie-Werten befüllen (überschreibt Server-Werte nur wenn leer)
        if (nummerInput && !nummerInput.value && filterData.nummer) {
            nummerInput.value = filterData.nummer;
        }
        if (codexInput && !codexInput.value && filterData.codex) {
            codexInput.value = filterData.codex;
        }
        if (sucheInput && !sucheInput.value && filterData.suche) {
            sucheInput.value = filterData.suche;
        }
        if (datumVonInput && filterData.datum_von) {
            datumVonInput.value = filterData.datum_von;
        }
        if (datumBisInput && filterData.datum_bis) {
            datumBisInput.value = filterData.datum_bis;
        }
        if (statusInput && !statusInput.value && filterData.status_filter) {
            statusInput.value = filterData.status_filter;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Filter anwenden
    // ═══════════════════════════════════════════════════════════════════════════════
    function applyFilter() {
        const nummer = nummerInput?.value ?? '';
        const codex = codexInput?.value ?? '';
        const suche = sucheInput?.value ?? '';
        const datumVon = datumVonInput?.value ?? '';
        const datumBis = datumBisInput?.value ?? '';
        const status = statusInput?.value ?? '';

        const params = new URLSearchParams();

        if (nummer) params.append('nummer', nummer);
        if (codex) params.append('codex', codex);
        if (suche) params.append('suche', suche);
        if (datumVon) params.append('datum_von', datumVon);
        if (datumBis) params.append('datum_bis', datumBis);
        if (status) params.append('status_filter', status);
        // Bei neuem Filter: Page auf 1 zurücksetzen (nicht mitgeben)

        const targetUrl = params.toString()
            ? baseIndexUrl + '?' + params.toString()
            : baseIndexUrl;

        window.location.href = targetUrl;
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Filter zurücksetzen
    // ═══════════════════════════════════════════════════════════════════════════════
    function resetFilter() {
        // Cookie löschen
        deleteCookie(COOKIE_NAME);
        
        // Zur Index-Seite ohne Parameter
        window.location.href = baseIndexUrl;
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Event Listener
    // ═══════════════════════════════════════════════════════════════════════════════
    if (btnFilter) {
        btnFilter.addEventListener('click', applyFilter);
    }

    if (btnReset) {
        btnReset.addEventListener('click', resetFilter);
    }

    // Status-Select: Sofort filtern bei Änderung
    if (statusInput) {
        statusInput.addEventListener('change', applyFilter);
    }

    // Enter-Taste in Eingabefeldern
    [nummerInput, codexInput, sucheInput, datumVonInput, datumBisInput].forEach(function(input) {
        if (!input) return;
        input.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyFilter();
            }
        });
    });

    // ═══════════════════════════════════════════════════════════════════════════════
    // Pagination-Links: Cookie aktualisieren bei Klick
    // ═══════════════════════════════════════════════════════════════════════════════
    document.querySelectorAll('.pagination a').forEach(function(link) {
        link.addEventListener('click', function() {
            // Kurz verzögert speichern, damit die URL noch aktuell ist
            setTimeout(saveFilterToCookie, 100);
        });
    });

    // ═══════════════════════════════════════════════════════════════════════════════
    // Bei Klick auf Rechnungs-Links: Scroll-Position speichern
    // ═══════════════════════════════════════════════════════════════════════════════
    document.querySelectorAll('a[href*="/rechnung/"]').forEach(function(link) {
        link.addEventListener('click', saveFilterToCookie);
    });

    // ═══════════════════════════════════════════════════════════════════════════════
    // Scroll-Position wiederherstellen
    // ═══════════════════════════════════════════════════════════════════════════════
    function restoreScrollPosition() {
        const filterData = getCookie(COOKIE_NAME);
        if (!filterData || !filterData.scrollY) return;
        
        // Scroll-Position wiederherstellen (kurz verzögert für DOM-Rendering)
        setTimeout(function() {
            window.scrollTo(0, filterData.scrollY);
        }, 100);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Initialisierung
    // ═══════════════════════════════════════════════════════════════════════════════
    // 1. Prüfen ob Redirect aus Cookie nötig
    restoreFromCookieIfNeeded();
    
    // 2. Cookie in Felder laden (falls kein Redirect)
    loadFilterFromCookie();
    
    // 3. Aktuelle Position speichern
    if (hasUrlFilterParams()) {
        saveFilterToCookie();
    }
    
    // 4. Scroll-Position wiederherstellen
    restoreScrollPosition();
    
    // 5. Scroll-Position bei Verlassen speichern
    window.addEventListener('beforeunload', saveFilterToCookie);

    // ═══════════════════════════════════════════════════════════════════════════════
    // BULK XML DOWNLOAD - Mehrfachauswahl
    // ═══════════════════════════════════════════════════════════════════════════════
    const selectAllDesktop = document.getElementById('selectAllDesktop');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCountEl = document.getElementById('selectedCount');
    const btnBulkXmlDownload = document.getElementById('btnBulkXmlDownload');
    const btnClearSelection = document.getElementById('btnClearSelection');

    function getSelectedCheckboxes() {
        return document.querySelectorAll('.xml-checkbox:checked');
    }

    function updateBulkActionBar() {
        const checked = getSelectedCheckboxes();
        const count = checked.length;

        if (selectedCountEl) selectedCountEl.textContent = count;

        if (bulkActionBar) {
            if (count > 0) {
                bulkActionBar.classList.remove('d-none');
            } else {
                bulkActionBar.classList.add('d-none');
            }
        }

        // "Alle auswählen"-Checkbox synchronisieren
        if (selectAllDesktop) {
            const allCheckboxes = document.querySelectorAll('.card.d-none.d-md-block .xml-checkbox');
            if (allCheckboxes.length > 0) {
                const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(allCheckboxes).some(cb => cb.checked);
                selectAllDesktop.checked = allChecked;
                selectAllDesktop.indeterminate = someChecked && !allChecked;
            }
        }
    }

    // Alle Checkboxen: Change-Event
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('xml-checkbox')) {
            updateBulkActionBar();
        }
    });

    // "Alle auswählen" Desktop
    if (selectAllDesktop) {
        selectAllDesktop.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.card.d-none.d-md-block .xml-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAllDesktop.checked;
            });
            updateBulkActionBar();
        });
    }

    // Auswahl aufheben
    if (btnClearSelection) {
        btnClearSelection.addEventListener('click', function() {
            document.querySelectorAll('.xml-checkbox').forEach(function(cb) {
                cb.checked = false;
            });
            if (selectAllDesktop) {
                selectAllDesktop.checked = false;
                selectAllDesktop.indeterminate = false;
            }
            updateBulkActionBar();
        });
    }

    // Bulk XML Download - einzelne Dateien herunterladen
    if (btnBulkXmlDownload) {
        btnBulkXmlDownload.addEventListener('click', function() {
            const checked = getSelectedCheckboxes();
            if (checked.length === 0) return;

            const ids = Array.from(checked).map(function(cb) { return cb.value; });

            // Route-Template mit Platzhalter (Blade generiert die korrekte URL)
            const routeTemplate = "<?php echo e(route('rechnung.xml.download', ['rechnung' => '__ID__'])); ?>";

            // Einzelne XML-Dateien nacheinander herunterladen
            // Kleiner Delay zwischen Downloads, damit der Browser nicht blockiert
            ids.forEach(function(id, index) {
                setTimeout(function() {
                    const url = routeTemplate.replace('__ID__', id);
                    const link = document.createElement('a');
                    link.href = url;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }, index * 300);
            });
        });
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/rechnung/index.blade.php ENDPATH**/ ?>