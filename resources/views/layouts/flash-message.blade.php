@php
    $hasErrors = isset($errors) && $errors->any();
    $hasAlert = Session::has('success') || Session::has('error') || Session::has('warning') || Session::has('info') || $hasErrors;
@endphp

@if ($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible auto-dismiss-alert fade show shadow-sm border-0 rounded-3 d-flex align-items-center mb-3" role="alert" style="transition: all 0.5s ease;">
        <i class="fe fe-check-circle fs-5 me-2 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong>Success!</strong> {{ $message }}
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($message = Session::get('error'))
    <div class="alert alert-danger alert-dismissible auto-dismiss-alert fade show shadow-sm border-0 rounded-3 d-flex align-items-center mb-3" role="alert" style="transition: all 0.5s ease;">
        <i class="fe fe-alert-circle fs-5 me-2 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong>Error!</strong> {{ $message }}
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($message = Session::get('warning'))
    <div class="alert alert-warning alert-dismissible auto-dismiss-alert fade show shadow-sm border-0 rounded-3 d-flex align-items-center mb-3" role="alert" style="transition: all 0.5s ease;">
        <i class="fe fe-alert-triangle fs-5 me-2 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong>Warning!</strong> {{ $message }}
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($message = Session::get('info'))
    <div class="alert alert-info alert-dismissible auto-dismiss-alert fade show shadow-sm border-0 rounded-3 d-flex align-items-center mb-3" role="alert" style="transition: all 0.5s ease;">
        <i class="fe fe-info fs-5 me-2 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong>Info:</strong> {{ $message }}
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($hasErrors)
    <div class="alert alert-danger alert-dismissible auto-dismiss-alert fade show shadow-sm border-0 rounded-3 mb-3" role="alert" style="transition: all 0.5s ease;">
        <div class="d-flex align-items-center mb-1">
            <i class="fe fe-alert-octagon fs-5 me-2 flex-shrink-0"></i>
            <strong>Please check the form below for errors:</strong>
        </div>
        <ul class="mb-0 ps-4 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($hasAlert)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            const alerts = document.querySelectorAll('.auto-dismiss-alert');
            alerts.forEach(function (alertEl) {
                alertEl.style.opacity = '0';
                alertEl.style.transform = 'translateY(-10px)';
                setTimeout(function () {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                        const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                        if (bsAlert) bsAlert.close();
                    } else {
                        alertEl.remove();
                    }
                }, 500);
            });
        }, 5000);
    });
</script>
@endif
