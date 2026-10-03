<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Tracking</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrapicons.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('styles')
</head>
<body>
    @php
        $loggedInAdminAndDriver = session('admin_and_driver_id')
            ? \App\Models\Tenant\AdminAndDriver::find(session('admin_and_driver_id'))
            : null;
        $allowedModules = app(\App\Services\RolePermissionService::class)->allowedModules();
    @endphp
    <div class="top-navbar">
        <a href="{{ route('dashboard.index') }}" class="navbar-brand-title">TRACKING</a>
        <div class="nav-menu-wrapper">
            <div class="nav-dropdown-container">
                <a href="{{ route('dashboard.index') }}" class="nav-dropdown-trigger"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </div>
            @if (array_intersect(['parents', 'students', 'class-sections', 'vehicles', 'vehicle-routes', 'stops'], $allowedModules))
                <div class="nav-dropdown-container">
                    <a href="#" class="nav-dropdown-trigger">Masters <i class="bi bi-caret-down-fill"></i></a>
                    <ul class="nav-dropdown-menu">
                        @permission('parents')
                            <li><a href="{{ route('parents.index') }}">Parents</a></li>
                        @endpermission
                        @permission('students')
                            <li><a href="{{ route('students.index') }}">Students</a></li>
                        @endpermission
                        @permission('class-sections')
                            <li><a href="{{ route('class-sections.index') }}">Class / Sections</a></li>
                        @endpermission
                        @permission('vehicles')
                            <li><a href="{{ route('vehicles.index') }}">Vehicles</a></li>
                        @endpermission
                        @permission('vehicle-routes')
                            <li><a href="{{ route('vehicle-routes.index') }}">Routes</a></li>
                        @endpermission
                        @permission('stops')
                            <li><a href="{{ route('stops.index') }}">Stops</a></li>
                        @endpermission
                    </ul>
                </div>
            @endif
            @if (array_intersect(['student-route-assignments', 'active-trips'], $allowedModules))
                <div class="nav-dropdown-container">
                    <a href="#" class="nav-dropdown-trigger">Assignments <i class="bi bi-caret-down-fill"></i></a>
                    <ul class="nav-dropdown-menu">
                        @permission('student-route-assignments')
                            <li><a href="{{ route('student-route-assignments.index') }}">Student Routes</a></li>
                        @endpermission
                        @permission('active-trips')
                            <li><a href="{{ route('active-trips.index') }}">Active Trips</a></li>
                        @endpermission
                    </ul>
                </div>
            @endif
            @if (array_intersect(['live-vehicle-locations', 'vehicle-location-history'], $allowedModules))
                <div class="nav-dropdown-container">
                    <a href="#" class="nav-dropdown-trigger">Tracking <i class="bi bi-caret-down-fill"></i></a>
                    <ul class="nav-dropdown-menu">
                        @permission('live-vehicle-locations')
                            <li><a href="{{ route('live-vehicle-locations.index') }}">Live Locations</a></li>
                        @endpermission
                        @permission('vehicle-location-history')
                            <li><a href="{{ route('vehicle-location-history.index') }}">Location History</a></li>
                        @endpermission
                    </ul>
                </div>
            @endif
            @permission('admin-and-drivers')
                <div class="nav-dropdown-container">
                    <a href="#" class="nav-dropdown-trigger">Admin <i class="bi bi-caret-down-fill"></i></a>
                    <ul class="nav-dropdown-menu nav-dropdown-menu-right">
                        <li><a href="{{ route('admin-and-drivers.index') }}">Admin & Drivers</a></li>
                    </ul>
                </div>
            @endpermission
        </div>
        <div class="user-profile-block">
            <div style="width: 26px; height: 26px; background-color: #fff; color: var(--header-dark); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.75rem;">
                {{ $loggedInAdminAndDriver ? strtoupper(substr($loggedInAdminAndDriver->full_name, 0, 1)) : 'U' }}
            </div>
            <span>{{ $loggedInAdminAndDriver?->full_name ?: 'User' }}</span>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-light">Logout</button>
            </form>
        </div>
    </div>

    <div class="page-content-body">
        @yield('content')
    </div>

    <div class="system-footer-bar">
        <span>&copy; 2026 <strong class="text-primary text-decoration-none">School Bus Tracking</strong></span>
        <span>All Rights Reserved</span>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/searchable-dropdown.js') }}"></script>
    @stack('scripts')
</body>
</html>
