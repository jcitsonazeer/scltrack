@extends('layouts.headerfooter')

@section('content')
<div>
    <div class="module-card">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
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

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="module-title-header mb-0">View Active Trips</div>
            @permission('active-trips', 'create')
                <a href="{{ route('active-trips.create') }}" class="btn btn-primary">Add Trip</a>
            @endpermission
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('active-trips.index') }}">
                    <input type="hidden" name="search" value="{{ $search }}">
                    <select name="per_page" class="form-select py-0 px-2" style="width: 80px; height: 32px; border-radius: 0;" onchange="this.form.submit()">
                        @foreach ([5, 10, 25, 50] as $size)
                            <option value="{{ $size }}" {{ $perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
                <span>entries</span>
            </div>

            <div class="col-auto d-flex align-items-center gap-2">
                <span>Search:</span>
                <form method="GET" action="{{ route('active-trips.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search trip" style="width: 180px; height: 32px; border-radius: 0;">
                </form>
            </div>
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Route</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Ended</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activeTrips as $activeTrip)
                        <tr>
                            <td>{{ $activeTrips->firstItem() + $loop->index }}</td>
                            <td>{{ $activeTrip->trip_date ? $activeTrip->trip_date->format('d-m-Y') : '-' }}</td>
                            <td>{{ $activeTrip->route?->display_name ?: '-' }}</td>
                            <td>{{ $activeTrip->vehicle?->vehicle_number ?: '-' }}</td>
                            <td>{{ $activeTrip->driver?->full_name ?: '-' }}</td>
                            <td>{{ $activeTrip->route?->trip_type ?: '-' }}</td>
                            <td>
                                @if ($activeTrip->trip_status === 'started')
                                    <span class="badge badge-success">STARTED</span>
                                @elseif ($activeTrip->trip_status === 'finished')
                                    <span class="badge bg-primary">FINISHED</span>
                                @elseif ($activeTrip->trip_status === 'cancelled')
                                    <span class="badge badge-danger">CANCELLED</span>
                                @else
                                    <span class="badge bg-secondary">{{ strtoupper($activeTrip->trip_status ?: '-') }}</span>
                                @endif
                            </td>
                            <td>{{ $activeTrip->started_at ? $activeTrip->started_at->format('d-m-Y H:i') : '-' }}</td>
                            <td>{{ $activeTrip->ended_at ? $activeTrip->ended_at->format('d-m-Y H:i') : '-' }}</td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('active-trips.show', $activeTrip) }}" class="grid-btn-edit"><i class="bi bi-eye"></i>View</a>
                                @permission('active-trips', 'update')
                                    <a href="{{ route('active-trips.edit', $activeTrip) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                                @permission('active-trips', 'delete')
                                    <form action="{{ route('active-trips.destroy', $activeTrip) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this active trip?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid-btn-delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-3" style="background-color: #fff;">No active trips found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $activeTrips->count() ? $activeTrips->firstItem() : 0 }} to {{ $activeTrips->count() ? $activeTrips->lastItem() : 0 }} of {{ $activeTrips->total() }} entries
            </div>
            <div>{{ $activeTrips->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
setTimeout(function () {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alertBox) {
        alertBox.style.display = 'none';
    });
}, 5000);
</script>
@endpush
@endsection
