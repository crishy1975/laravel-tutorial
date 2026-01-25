{{-- 
    resources/views/partials/rechnung-warnings.blade.php
    
    EINBINDEN in resources/views/rechnung/form.blade.php (ganz oben nach @section('content')):
    @include('partials.rechnung-warnings')
--}}

{{-- Standard Laravel Flash Messages --}}
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {!! session('warning') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Spezielle Rechnungs-Warnungen (Array) --}}
@if(session('rechnung_warnings'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Hinweise zur erstellten Rechnung:
        </h5>
        <hr>
        <ul class="mb-0">
            @foreach(session('rechnung_warnings') as $warning)
                <li>{!! $warning !!}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
