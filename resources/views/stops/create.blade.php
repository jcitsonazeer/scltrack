@extends('layouts.headerfooter')

@section('content')
<div class="module-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="module-title-header mb-0">Add Stop</div>
        <a href="{{ route('stops.index') }}" class="btn btn-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @unless ($hasAssignedRoutes)
        <div class="alert alert-danger">
            No route is assigned to you yet. Ask an administrator to assign you a route before adding a stop.
        </div>
    @endunless

    <form method="POST" action="{{ route('stops.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                @php
                    $selectedRouteId = old('route_id');
                    $selectedRoute = $routes->firstWhere('id', (int) $selectedRouteId);
                    $selectedRouteLabel = $selectedRoute
                        ? collect([$selectedRoute->route_name, $selectedRoute->route_code, $selectedRoute->trip_type])->filter()->implode(' - ')
                        : '';
                @endphp
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Route <span class="text-danger">*</span></label>
                <div class="position-relative" data-searchable-dropdown data-url="{{ route('lookups.routes') }}" data-fill-target="#stop-order-input" data-fill-field="next_order">
                    <input
                        type="text"
                        class="form-control"
                        data-search-input
                        name="route_search"
                        value="{{ old('route_search', $selectedRouteLabel) }}"
                        placeholder="Search by route name or code"
                        autocomplete="off"
                        required
                    >
                    <input type="hidden" name="route_id" id="stop-route-id" data-value-input value="{{ $selectedRouteId }}">
                    <div class="list-group searchable-dropdown-results shadow-sm d-none" data-search-results></div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Stop Name <span class="text-danger">*</span></label>
                <input type="text" name="stop_name" class="form-control" value="{{ old('stop_name') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Stop Order <span class="text-danger">*</span></label>
                <input type="number" name="stop_order" id="stop-order-input" class="form-control" value="{{ old('stop_order', 1) }}" min="1" required>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Latitude @if ($requireCoordinates)<span class="text-danger">*</span>@endif</label>
                <input type="number" step="0.0000001" name="latitude" id="stop-latitude" class="form-control" value="{{ old('latitude') }}" @if ($requireCoordinates) required readonly @endif>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Longitude @if ($requireCoordinates)<span class="text-danger">*</span>@endif</label>
                <input type="number" step="0.0000001" name="longitude" id="stop-longitude" class="form-control" value="{{ old('longitude') }}" @if ($requireCoordinates) required readonly @endif>
            </div>
            <div class="col-md-6">
                @php
                    $statusOptions = collect([
                        ['id' => '1', 'text' => 'Active'],
                        ['id' => '0', 'text' => 'Inactive'],
                    ]);
                    $selectedStatus = (string) old('is_active', '1');
                @endphp
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Status <span class="text-danger">*</span></label>
                <div class="position-relative" data-searchable-dropdown data-options='@json($statusOptions)'>
                    <input type="text" class="form-control" data-search-input value="{{ $selectedStatus === '1' ? 'Active' : 'Inactive' }}" placeholder="Search status" autocomplete="off" required>
                    <input type="hidden" name="is_active" id="stop-status" data-value-input value="{{ $selectedStatus }}">
                    <div class="list-group searchable-dropdown-results shadow-sm d-none" data-search-results></div>
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2 align-items-center flex-wrap">
            <button type="button" class="btn btn-secondary" id="gps-button">
                <i class="bi bi-crosshair"></i> Use My Current Location
            </button>
            <span class="badge badge-danger" id="gps-status">LOCATION NOT READY</span>
            <button type="submit" class="btn btn-primary" id="submit-stop-button" @if ($requireCoordinates) disabled @endif>Submit</button>
            <a href="{{ route('stops.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var gpsButton = document.getElementById('gps-button');
    var gpsStatus = document.getElementById('gps-status');
    var submitButton = document.getElementById('submit-stop-button');
    var latitudeInput = document.getElementById('stop-latitude');
    var longitudeInput = document.getElementById('stop-longitude');

    function setStatus(text, isReady) {
        gpsStatus.textContent = text;
        gpsStatus.className = 'badge ' + (isReady ? 'badge-success' : 'badge-danger');
    }

    function capture() {
        if (!navigator.geolocation) {
            setStatus('GPS NOT SUPPORTED', false);
            return;
        }

        setStatus('LOCATING...', false);
        gpsButton.disabled = true;

        navigator.geolocation.getCurrentPosition(
            function (position) {
                latitudeInput.value = position.coords.latitude.toFixed(7);
                longitudeInput.value = position.coords.longitude.toFixed(7);
                setStatus('LOCATION CAPTURED', true);
                gpsButton.disabled = false;
                submitButton.disabled = false;
            },
            function () {
                setStatus('LOCATION DENIED', false);
                gpsButton.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    gpsButton.addEventListener('click', capture);

    @if ($requireCoordinates)
        capture();
    @endif
})();
</script>
@endpush
@endsection
