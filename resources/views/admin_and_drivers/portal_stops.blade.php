<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Update Stop Location - Tracking</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrapicons.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <div class="top-navbar">
        <span class="navbar-brand-title">TRACKING</span>
        <div class="user-profile-block">
            <span>{{ $user['full_name'] }}</span>
            <a href="{{ route('app.driver.dashboard') }}" class="btn btn-sm btn-light">Dashboard</a>
            <form method="POST" action="{{ route('app.driver.logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-light">Logout</button>
            </form>
        </div>
    </div>

    <div class="page-content-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="module-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="module-title-header mb-0">Add Stop At My Location</div>
                <span class="badge badge-success" id="gps-status">LOCATION NOT READY</span>
            </div>

            @if ($routes->isEmpty())
                <div class="alert alert-danger mb-0">
                    No route is assigned to you yet. Ask an administrator to assign you a route before adding stops.
                </div>
            @else
                <form method="POST" action="{{ route('app.driver.stops.store') }}" id="add-stop-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label mb-1 text-muted">Route <span class="text-danger">*</span></label>
                            <select name="route_id" class="form-select" required>
                                <option value="">Select route</option>
                                @foreach ($routes as $route)
                                    <option value="{{ $route->id }}" {{ (string) old('route_id') === (string) $route->id ? 'selected' : '' }}>
                                        {{ collect([$route->route_name, $route->route_code, $route->trip_type])->filter()->implode(' - ') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 text-muted">Stop Name <span class="text-danger">*</span></label>
                            <input type="text" name="stop_name" class="form-control" value="{{ old('stop_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1 text-muted">Latitude <span class="text-danger">*</span></label>
                            <input type="text" name="latitude" id="new-latitude" class="form-control" value="{{ old('latitude') }}" readonly required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1 text-muted">Longitude <span class="text-danger">*</span></label>
                            <input type="text" name="longitude" id="new-longitude" class="form-control" value="{{ old('longitude') }}" readonly required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1 text-muted">Stop Order</label>
                            <input type="number" name="stop_order" class="form-control" value="{{ old('stop_order') }}" min="1" placeholder="Auto">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-secondary" id="refresh-location-button">
                            <i class="bi bi-crosshair"></i> Use My Current Location
                        </button>
                        <button type="submit" class="btn btn-primary" id="submit-stop-button" disabled>Submit Stop</button>
                    </div>
                </form>
            @endif
        </div>

        <div class="module-card">
            <div class="module-title-header mb-3">My Route Stops</div>

            <div class="table-responsive-wrapper">
                <table class="custom-data-grid">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Route</th>
                            <th>Stop</th>
                            <th>Order</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stops as $stop)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $stop->route?->route_name ?: '-' }}</td>
                                <td>{{ $stop->stop_name }}</td>
                                <td>{{ $stop->stop_order }}</td>
                                <td>{{ $stop->latitude ?? '-' }}</td>
                                <td>{{ $stop->longitude ?? '-' }}</td>
                                <td class="text-center" style="white-space: nowrap;">
                                    <button
                                        type="button"
                                        class="grid-btn-edit move-stop-button"
                                        data-update-url="{{ route('app.driver.stops.location', $stop->id) }}"
                                        data-stop-name="{{ $stop->stop_name }}"
                                    >
                                        <i class="bi bi-geo-alt"></i> Update To My Location
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3" style="background-color: #fff;">No stops found for your routes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form method="POST" action="" id="move-stop-form" class="d-none">
                @csrf
                <input type="hidden" name="latitude" id="move-latitude">
                <input type="hidden" name="longitude" id="move-longitude">
            </form>
        </div>
    </div>

    <div class="system-footer-bar">
        <span>&copy; 2026 <strong class="text-primary text-decoration-none">School Bus Tracking</strong></span>
        <span>All Rights Reserved</span>
    </div>

    <script>
        (function () {
            var statusBadge = document.getElementById('gps-status');
            var latitudeInput = document.getElementById('new-latitude');
            var longitudeInput = document.getElementById('new-longitude');
            var refreshButton = document.getElementById('refresh-location-button');
            var submitButton = document.getElementById('submit-stop-button');
            var moveForm = document.getElementById('move-stop-form');
            var moveLatitude = document.getElementById('move-latitude');
            var moveLongitude = document.getElementById('move-longitude');
            var currentPosition = null;

            function setStatus(text, isReady) {
                if (!statusBadge) {
                    return;
                }

                statusBadge.textContent = text;
                statusBadge.className = 'badge ' + (isReady ? 'badge-success' : 'badge-danger');
            }

            function fillPosition(position) {
                currentPosition = position;
                var latitude = position.coords.latitude.toFixed(7);
                var longitude = position.coords.longitude.toFixed(7);

                if (latitudeInput) {
                    latitudeInput.value = latitude;
                }

                if (longitudeInput) {
                    longitudeInput.value = longitude;
                }

                setStatus('LOCATION READY', true);

                if (submitButton) {
                    submitButton.disabled = false;
                }
            }

            function requestLocation() {
                if (!navigator.geolocation) {
                    setStatus('GPS NOT SUPPORTED', false);
                    return;
                }

                setStatus('LOCATING...', false);

                navigator.geolocation.getCurrentPosition(
                    fillPosition,
                    function () {
                        setStatus('LOCATION DENIED', false);
                    },
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                );
            }

            if (refreshButton) {
                refreshButton.addEventListener('click', requestLocation);
            }

            requestLocation();

            document.querySelectorAll('.move-stop-button').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!navigator.geolocation) {
                        alert('GPS is not supported on this device.');
                        return;
                    }

                    if (!currentPosition) {
                        alert('Waiting for your location. Please try again in a moment.');
                        requestLocation();
                        return;
                    }

                    var stopName = button.getAttribute('data-stop-name') || 'this stop';

                    if (!confirm('Move "' + stopName + '" to your current location?')) {
                        return;
                    }

                    moveForm.action = button.getAttribute('data-update-url');
                    moveLatitude.value = currentPosition.coords.latitude.toFixed(7);
                    moveLongitude.value = currentPosition.coords.longitude.toFixed(7);
                    moveForm.submit();
                });
            });
        })();
    </script>
</body>
</html>