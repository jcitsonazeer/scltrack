@extends('layouts.headerfooter')

@section('content')
<div>
    <div class="module-card">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="module-title-header mb-0">Dashboard</div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted">Updated {{ now()->format('d-m-Y H:i') }}</span>
                <a href="{{ route('dashboard.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Refresh</a>
            </div>
        </div>

        <div class="row g-3 dashboard-stat-row">
            @permission('students')
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="{{ route('students.index') }}" class="dashboard-stat-card text-decoration-none">
                        <div class="dashboard-stat-label"><i class="bi bi-people-fill"></i> Students</div>
                        <div class="dashboard-stat-value">{{ $stats['totalStudents'] }}</div>
                        <div class="dashboard-stat-meta">{{ $stats['activeStudents'] }} active</div>
                    </a>
                </div>
            @endpermission
            @permission('active-trips')
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="{{ route('active-trips.index') }}" class="dashboard-stat-card text-decoration-none">
                        <div class="dashboard-stat-label"><i class="bi bi-bus-front-fill"></i> Buses On Trip</div>
                        <div class="dashboard-stat-value">{{ $stats['busesOnTrip'] }}</div>
                        <div class="dashboard-stat-meta">Trips currently started</div>
                    </a>
                </div>
            @endpermission
            @permission('admin-and-drivers')
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="{{ route('admin-and-drivers.index') }}" class="dashboard-stat-card text-decoration-none">
                        <div class="dashboard-stat-label"><i class="bi bi-person-badge-fill"></i> Active Drivers</div>
                        <div class="dashboard-stat-value">{{ $stats['activeDrivers'] }}</div>
                        <div class="dashboard-stat-meta">{{ $stats['driversOnTrip'] }} on trip / {{ $stats['totalDrivers'] }} total</div>
                    </a>
                </div>
            @endpermission
            @permission('live-vehicle-locations')
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="{{ route('live-vehicle-locations.index') }}" class="dashboard-stat-card text-decoration-none">
                        <div class="dashboard-stat-label"><i class="bi bi-geo-alt-fill"></i> Live Locations</div>
                        <div class="dashboard-stat-value">{{ $stats['liveLocations'] }}</div>
                        <div class="dashboard-stat-meta">Updated in last {{ \App\Services\DashboardService::LIVE_LOCATION_STALE_MINUTES }} min</div>
                    </a>
                </div>
            @endpermission
        </div>
    </div>

    <div class="module-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="module-title-header mb-0">Fleet &amp; Route Snapshot</div>
        </div>

        <div class="row g-3 dashboard-stat-row">
            <div class="col-6 col-lg-2">
                <div class="dashboard-stat-card dashboard-stat-card-plain">
                    <div class="dashboard-stat-label"><i class="bi bi-person-lines-fill"></i> Parents</div>
                    <div class="dashboard-stat-value">{{ $stats['totalParents'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="dashboard-stat-card dashboard-stat-card-plain">
                    <div class="dashboard-stat-label"><i class="bi bi-bus-fill"></i> Vehicles</div>
                    <div class="dashboard-stat-value">{{ $stats['activeVehicles'] }}</div>
                    <div class="dashboard-stat-meta">of {{ $stats['totalVehicles'] }} total</div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="dashboard-stat-card dashboard-stat-card-plain">
                    <div class="dashboard-stat-label"><i class="bi bi-signpost-split-fill"></i> Routes</div>
                    <div class="dashboard-stat-value">{{ $stats['totalRoutes'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="dashboard-stat-card dashboard-stat-card-plain">
                    <div class="dashboard-stat-label"><i class="bi bi-geo-fill"></i> Stops</div>
                    <div class="dashboard-stat-value">{{ $stats['totalStops'] }}</div>
                </div>
            </div>
            @permission('student-route-assignments')
                <div class="col-6 col-lg-2">
                    <a href="{{ route('student-route-assignments.index') }}" class="dashboard-stat-card dashboard-stat-card-plain text-decoration-none">
                        <div class="dashboard-stat-label"><i class="bi bi-clipboard-check-fill"></i> Student Routes</div>
                        <div class="dashboard-stat-value">{{ $stats['activeStudentRouteAssignments'] }}</div>
                        <div class="dashboard-stat-meta">Active assignments</div>
                    </a>
                </div>
            @endpermission
            <div class="col-6 col-lg-2">
                <div class="dashboard-stat-card dashboard-stat-card-plain">
                    <div class="dashboard-stat-label"><i class="bi bi-bell-fill"></i> Unread Alerts</div>
                    <div class="dashboard-stat-value">{{ $stats['unreadNotifications'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="module-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="module-title-header mb-0">Buses On Trip</div>
            @permission('active-trips')
                <a href="{{ route('active-trips.index') }}" class="btn btn-secondary btn-sm">View All Trips</a>
            @endpermission
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Route</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Started</th>
                        <th>Last Location</th>
                        <th>Speed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runningTrips as $runningTrip)
                        @php
                            $liveLocation = $runningTrip->liveVehicleLocation;
                            $isStale = ! $liveLocation
                                || ! $liveLocation->recorded_at
                                || $liveLocation->recorded_at->lt($liveCutoff);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $runningTrip->route?->display_name ?: '-' }}</td>
                            <td>{{ $runningTrip->vehicle?->vehicle_number ?: '-' }}</td>
                            <td>{{ $runningTrip->driver?->full_name ?: '-' }}</td>
                            <td>{{ $runningTrip->started_at ? $runningTrip->started_at->format('d-m-Y H:i') : '-' }}</td>
                            <td>
                                @if (! $liveLocation || ! $liveLocation->recorded_at)
                                    <span class="badge badge-danger">NO LOCATION</span>
                                @elseif ($isStale)
                                    <span class="badge badge-danger">STALE</span>
                                    <div class="text-muted">{{ $liveLocation->recorded_at->format('d-m-Y H:i') }}</div>
                                @else
                                    <span class="badge badge-success">LIVE</span>
                                    <div class="text-muted">{{ $liveLocation->recorded_at->format('d-m-Y H:i') }}</div>
                                @endif
                            </td>
                            <td>{{ $liveLocation && $liveLocation->speed !== null ? $liveLocation->speed . ' km/h' : '-' }}</td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('active-trips.show', $runningTrip) }}" class="grid-btn-view"><i class="bi bi-eye"></i>View</a>
                                @permission('active-trips', 'update')
                                    <a href="{{ route('active-trips.edit', $runningTrip) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3" style="background-color: #fff;">No buses are on trip right now.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="module-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="module-title-header mb-0">Alerts</div>
            @if ($criticalAlertCount > 0)
                <span class="badge badge-danger">{{ $criticalAlertCount }} NEEDING ATTENTION</span>
            @else
                <span class="badge badge-success">ALL CLEAR</span>
            @endif
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Severity</th>
                        <th>Alert</th>
                        <th>Detail</th>
                        <th>When</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $alert)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if ($alert['severity'] === 'danger')
                                    <span class="badge badge-danger">{{ $alert['severity_label'] }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ $alert['severity_label'] }}</span>
                                @endif
                            </td>
                            <td>{{ $alert['title'] }}</td>
                            <td>{{ $alert['detail'] ?: '-' }}</td>
                            <td>{{ $alert['occurred_at'] ? $alert['occurred_at']->format('d-m-Y H:i') : '-' }}</td>
                            <td class="text-center" style="white-space: nowrap;">
                                @permission($alert['action_module'], 'view')
                                    <a href="{{ $alert['action_url'] }}" class="grid-btn-view">{{ $alert['action_label'] }}</a>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3" style="background-color: #fff;">No alerts. Everything looks normal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="module-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="module-title-header mb-0">Latest Vehicle Locations</div>
                    @permission('live-vehicle-locations')
                    <a href="{{ route('live-vehicle-locations.index') }}" class="btn btn-secondary btn-sm">View All</a>
                @endpermission
                </div>

                <div class="table-responsive-wrapper">
                    <table class="custom-data-grid">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vehicle</th>
                                <th>Trip</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Recorded</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($liveLocations as $liveLocation)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $liveLocation->vehicle?->vehicle_number ?: '-' }}</td>
                                    <td>{{ $liveLocation->activeTrip?->route?->display_name ?: '-' }}</td>
                                    <td>{{ $liveLocation->latitude }}</td>
                                    <td>{{ $liveLocation->longitude }}</td>
                                    <td>{{ $liveLocation->recorded_at ? $liveLocation->recorded_at->format('d-m-Y H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3" style="background-color: #fff;">No locations recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="module-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="module-title-header mb-0">Recent Notifications</div>
                </div>

                <div class="table-responsive-wrapper">
                    <table class="custom-data-grid">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Notification</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentNotifications as $notification)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div>{{ $notification->title ?: '-' }}</div>
                                        <div class="text-muted">{{ str($notification->message)->limit(60) }}</div>
                                    </td>
                                    <td>
                                        @if ($notification->is_read)
                                            <span class="badge bg-secondary">READ</span>
                                        @else
                                            <span class="badge badge-success">UNREAD</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3" style="background-color: #fff;">No notifications.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection