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
            <div class="module-title-header mb-0">View Live Vehicle Locations</div>
            @permission('live-vehicle-locations', 'create')
                <a href="{{ route('live-vehicle-locations.create') }}" class="btn btn-primary">Add Live Location</a>
            @endpermission
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('live-vehicle-locations.index') }}">
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
                <form method="GET" action="{{ route('live-vehicle-locations.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search location" style="width: 180px; height: 32px; border-radius: 0;">
                </form>
            </div>
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Vehicle</th>
                        <th>Active Trip</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Speed</th>
                        <th>Heading</th>
                        <th>Ignition</th>
                        <th>Recorded At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($liveLocations as $liveLocation)
                        <tr>
                            <td>{{ $liveLocations->firstItem() + $loop->index }}</td>
                            <td>{{ $liveLocation->vehicle?->vehicle_number ?: '-' }}</td>
                            <td>{{ $liveLocation->activeTrip?->display_name ?: '-' }}</td>
                            <td>{{ $liveLocation->latitude }}</td>
                            <td>{{ $liveLocation->longitude }}</td>
                            <td>{{ $liveLocation->speed !== null ? $liveLocation->speed : '-' }}</td>
                            <td>{{ $liveLocation->heading !== null ? $liveLocation->heading : '-' }}</td>
                            <td>{{ $liveLocation->ignition_on ? 'On' : 'Off' }}</td>
                            <td>{{ $liveLocation->recorded_at ? $liveLocation->recorded_at->format('d-m-Y H:i') : '-' }}</td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('live-vehicle-locations.show', $liveLocation) }}" class="grid-btn-edit"><i class="bi bi-eye"></i>View</a>
                                @permission('live-vehicle-locations', 'update')
                                    <a href="{{ route('live-vehicle-locations.edit', $liveLocation) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                                @permission('live-vehicle-locations', 'delete')
                                    <form action="{{ route('live-vehicle-locations.destroy', $liveLocation) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this live location?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid-btn-delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-3" style="background-color: #fff;">No live vehicle locations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $liveLocations->count() ? $liveLocations->firstItem() : 0 }} to {{ $liveLocations->count() ? $liveLocations->lastItem() : 0 }} of {{ $liveLocations->total() }} entries
            </div>
            <div>{{ $liveLocations->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection
