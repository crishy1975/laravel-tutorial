<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UschiWeb – Select2 Test</title>

  {{-- Bootstrap + Icons --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  {{-- Select2 + Theme --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

  <style>
    body {
      background-color: #f8f9fa;
    }

    .navbar-brand {
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .select2-container {
      width: 100% !important;
    }

    .select2-container--bootstrap-5 .select2-selection--single {
      height: calc(2.5rem + 2px);
      padding: 0.375rem 0.75rem;
      display: flex;
      align-items: center;
    }

    .select2-container--bootstrap-5 .select2-search .select2-search__field {
      width: 100% !important;
      height: calc(2.25rem);
    }

    footer {
      text-align: center;
      padding: 1rem;
      margin-top: 3rem;
      color: #6c757d;
    }

    /* 🔧 Select2 visuell exakt wie Bootstrap .form-select */
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

    /* 🔹 Hover & Fokus-Effekte wie Bootstrap */
    .select2-container--bootstrap-5 .select2-selection:hover {
      border-color: #86b7fe !important;
    }

    .select2-container--bootstrap-5.select2-container--focus .select2-selection {
      border-color: #86b7fe !important;
      box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .25) !important;
    }

    /* 🔹 Höhe & Pfeilposition korrigieren */
    .select2-container--bootstrap-5 .select2-selection__arrow {
      top: 50% !important;
      transform: translateY(-50%) !important;
      right: 0.75rem !important;
    }

    /* 🔹 Suchfeld im Dropdown */
    .select2-container--bootstrap-5 .select2-search .select2-search__field {
      width: 100% !important;
      height: calc(2.25rem);
      padding: 0.375rem 0.75rem;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
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
            <a class="nav-link active fw-bold" href="#">
              <i class="bi bi-search"></i> Select2 Test
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  {{-- 🔹 Hauptinhalt --}}
  <main class="container">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white">
        <h4 class="mb-0">
          <i class="bi bi-list-task"></i> Select2 mit Bootstrap 5 Layout
        </h4>
      </div>

      <div class="card-body">
        <div class="row g-4">
          {{-- Erstes Select --}}
          <div class="col-md-6">
            <label for="adresse1" class="form-label fw-semibold">
              <i class="bi bi-envelope"></i> Postadresse
            </label>
            <select id="adresse1" class="form-select js-select2" data-placeholder="— bitte wählen —" data-allow-clear="false">
              <option value=""></option>
              <option value="1">Resch GmbH Meisterbetrieb – Bozen</option>
              <option value="2">Resch Christian snc – Leifers</option>
              <option value="3">Thaler & Thaler – Bolzano</option>
              <option value="4">Baumbach, Jast and Littel – Maceybury</option>
            </select>
          </div>

          {{-- Zweites Select --}}
          <div class="col-md-6">
            <label for="adresse2" class="form-label fw-semibold">
              <i class="bi bi-receipt"></i> Rechnungsempfänger
            </label>
            <select id="adresse2" class="form-select js-select2" data-placeholder="— bitte wählen —" data-allow-clear="false">
              <option value=""></option>
              <option value="1">Resch GmbH Meisterbetrieb – Bozen</option>
              <option value="2">Resch Christian snc – Leifers</option>
              <option value="3">Thaler & Thaler – Bolzano</option>
              <option value="4">Baumbach, Jast and Littel – Maceybury</option>
            </select>
          </div>
        </div>
      </div>

      <div class="card-footer bg-white text-end">
        <button type="button" class="btn btn-primary">
          <i class="bi bi-check2-circle"></i> Speichern
        </button>
      </div>
    </div>
  </main>

  <footer>
    <small>&copy; 2025 Resch GmbH Meisterbetrieb – UschiWeb</small>
  </footer>

  {{-- 🔹 JS --}}
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

  <script>
    /**
     * Initialisiert alle Select2-Felder mit Bootstrap-5-Theme
     */
    function initSelect2(scope) {
      const $root = scope ? $(scope) : $(document);
      $root.find('select.js-select2').each(function() {
        const $el = $(this);

        // Bereits initialisiert? → zerstören
        if ($el.hasClass('select2-hidden-accessible')) {
          $el.select2('destroy');
        }

        // Neu initialisieren
        $el.select2({
          theme: 'bootstrap-5',
          width: '100%',
          placeholder: $el.data('placeholder') || 'Bitte wählen…',
          allowClear: true,
          language: {
            noResults: () => 'Keine Treffer gefunden',
            searching: () => 'Suche…'
          }
        });
      });
    }

    document.addEventListener('DOMContentLoaded', () => initSelect2());
  </script>

</body>

</html>