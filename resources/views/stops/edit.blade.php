@extends('layouts.headerfooter')

@section('content')
<div class="module-card">
    <div class="module-title-header mb-3">Edit Stop</div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('stops.update', $stop) }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                @php
                    $selectedRouteId = old('route_id', $stop->route_id);
                    $selectedRoute = $routes->firstWhere('id', (int) $selectedRouteId);
                    $selectedRouteLabel = $selectedRoute?->display_name ?: '';
                    $routeOptions = $routes->map(fn ($routeOption) => [
                        'id' => $routeOption->id,
                        'name' => $routeOption->route_name,
                        'code' => $routeOption->route_code,
                        'trip_type' => $routeOption->trip_type,
                        'label' => $routeOption->display_name,
                        'next_order' => ((int) $routeOption->stops_max_stop_order) + 1,
                    ])->values();
                @endphp
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Route <span class="text-danger">*</span></label>
                <div class="position-relative route-search-wrapper" data-route-search data-routes='@json($routeOptions)'>
                    <input
                        type="text"
                        class="form-control"
                        data-route-search-input
                        name="route_search"
                        value="{{ old('route_search', $selectedRouteLabel) }}"
                        placeholder="Search by route name or code"
                        autocomplete="off"
                        required
                    >
                    <input type="hidden" name="route_id" data-route-id-input value="{{ $selectedRouteId }}" required>
                    <div class="list-group route-search-results shadow-sm d-none" data-route-results></div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Stop Name <span class="text-danger">*</span></label>
                <input type="text" name="stop_name" class="form-control" value="{{ old('stop_name', $stop->stop_name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Stop Code</label>
                <div class="form-control bg-light">{{ $stop->stop_code ?: 'Will be generated after update' }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Stop Order <span class="text-danger">*</span></label>
                <input type="number" name="stop_order" data-stop-order-input class="form-control" value="{{ old('stop_order', $stop->stop_order) }}" min="1" required>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Latitude @if ($requireCoordinates)<span class="text-danger">*</span>@endif</label>
                <input type="number" step="0.0000001" name="latitude" id="stop-latitude" class="form-control" value="{{ old('latitude', $stop->latitude) }}" @if ($requireCoordinates) required readonly @endif>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Longitude @if ($requireCoordinates)<span class="text-danger">*</span>@endif</label>
                <input type="number" step="0.0000001" name="longitude" id="stop-longitude" class="form-control" value="{{ old('longitude', $stop->longitude) }}" @if ($requireCoordinates) required readonly @endif>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Status <span class="text-danger">*</span></label>
                <select name="is_active" class="form-select" required>
                    <option value="1" {{ old('is_active', $stop->is_active) == 1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('is_active', $stop->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2 align-items-center flex-wrap">
            <button type="button" class="btn btn-secondary" id="gps-button">
                <i class="bi bi-crosshair"></i> Update From My Current Location
            </button>
            <span class="badge badge-danger" id="gps-status">LOCATION NOT READY</span>
            <button type="submit" class="btn btn-primary" id="submit-stop-button">Update</button>
            <a href="{{ route('stops.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

@push('styles')
<style>
    .route-search-results {
        left: 0;
        max-height: 220px;
        overflow-y: auto;
        position: absolute;
        right: 0;
        top: calc(100% + 4px);
        z-index: 1050;
    }

    .route-search-results .list-group-item {
        cursor: pointer;
    }

    .route-search-results .route-code {
        font-size: 0.78rem;
    }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('[data-route-search]').forEach(function (wrapper) {
    var originalRouteId = '{{ $stop->route_id }}';
    var routes = JSON.parse(wrapper.dataset.routes || '[]');
    var searchInput = wrapper.querySelector('[data-route-search-input]');
    var routeIdInput = wrapper.querySelector('[data-route-id-input]');
    var results = wrapper.querySelector('[data-route-results]');
    var stopOrderInput = document.querySelector('[data-stop-order-input]');
    var selectedLabel = searchInput.value;

    function routeLabel(route) {
        return route.label || [route.name, route.code, route.trip_type].filter(Boolean).join(' - ');
    }

    function hideResults() {
        results.classList.add('d-none');
    }

    function selectRoute(route) {
        searchInput.value = routeLabel(route);
        selectedLabel = searchInput.value;
        routeIdInput.value = route.id;
        searchInput.setCustomValidity('');

        if (stopOrderInput && String(route.id) !== originalRouteId) {
            stopOrderInput.value = route.next_order;
        }

        hideResults();
    }

    function renderResults(clearSelection) {
        var query = searchInput.value.trim().toLowerCase();

        if (clearSelection && searchInput.value !== selectedLabel) {
            routeIdInput.value = '';
        }

        var matches = routes.filter(function (route) {
            var name = (route.name || '').toLowerCase();
            var code = (route.code || '').toLowerCase();
            var tripType = (route.trip_type || '').toLowerCase();

            return !query || name.indexOf(query) !== -1 || code.indexOf(query) !== -1 || tripType.indexOf(query) !== -1;
        }).slice(0, 10);

        results.innerHTML = '';

        if (!matches.length) {
            var emptyItem = document.createElement('div');
            emptyItem.className = 'list-group-item text-muted';
            emptyItem.textContent = 'No route found';
            results.appendChild(emptyItem);
            results.classList.remove('d-none');
            return;
        }

        matches.forEach(function (route) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';

            var name = document.createElement('div');
            name.className = 'fw-semibold';
            name.textContent = routeLabel(route);
            item.appendChild(name);

            item.addEventListener('mousedown', function (event) {
                event.preventDefault();
                selectRoute(route);
            });

            results.appendChild(item);
        });

        results.classList.remove('d-none');
    }

    searchInput.addEventListener('input', function () {
        renderResults(true);
    });

    searchInput.addEventListener('focus', function () {
        renderResults(false);
    });

    searchInput.addEventListener('blur', function () {
        setTimeout(hideResults, 150);
    });

    searchInput.form.addEventListener('submit', function (event) {
        if (!routeIdInput.value) {
            searchInput.setCustomValidity('Please select a route from the list.');
            searchInput.reportValidity();
            event.preventDefault();
            return;
        }

        searchInput.setCustomValidity('');
    });
});

(function () {
    var gpsButton = document.getElementById('gps-button');
    var gpsStatus = document.getElementById('gps-status');
    var latitudeInput = document.getElementById('stop-latitude');
    var longitudeInput = document.getElementById('stop-longitude');

    function setStatus(text, isReady) {
        gpsStatus.textContent = text;
        gpsStatus.className = 'badge ' + (isReady ? 'badge-success' : 'badge-danger');
    }

    gpsButton.addEventListener('click', function () {
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
            },
            function () {
                setStatus('LOCATION DENIED', false);
                gpsButton.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });
})();
</script>
@endpush
@endsection
