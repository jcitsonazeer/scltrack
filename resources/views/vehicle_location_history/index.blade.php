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
            <div class="module-title-header mb-0">View Vehicle Location History</div>
            @permission('vehicle-location-history', 'create')
                <a href="{{ route('vehicle-location-history.create') }}" class="btn btn-primary">Add History</a>
            @endpermission
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('vehicle-location-history.index') }}">
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
                <form method="GET" action="{{ route('vehicle-location-history.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search history" style="width: 180px; height: 32px; border-radius: 0;">
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
                    @forelse ($locationHistory as $history)
                        <tr>
                            <td>{{ $locationHistory->firstItem() + $loop->index }}</td>
                            <td>{{ $history->vehicle?->vehicle_number ?: '-' }}</td>
                            <td>{{ $history->activeTrip?->display_name ?: '-' }}</td>
                            <td>{{ $history->latitude }}</td>
                            <td>{{ $history->longitude }}</td>
                            <td>{{ $history->speed !== null ? $history->speed : '-' }}</td>
                            <td>{{ $history->heading !== null ? $history->heading : '-' }}</td>
                            <td>{{ $history->ignition_on ? 'On' : 'Off' }}</td>
                            <td>{{ $history->recorded_at ? $history->recorded_at->format('d-m-Y H:i') : '-' }}</td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('vehicle-location-history.show', $history) }}" class="grid-btn-edit"><i class="bi bi-eye"></i>View</a>
                                @permission('vehicle-location-history', 'update')
                                    <a href="{{ route('vehicle-location-history.edit', $history) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                                @permission('vehicle-location-history', 'delete')
                                    <form action="{{ route('vehicle-location-history.destroy', $history) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this history record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid-btn-delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-3" style="background-color: #fff;">No vehicle location history found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $locationHistory->count() ? $locationHistory->firstItem() : 0 }} to {{ $locationHistory->count() ? $locationHistory->lastItem() : 0 }} of {{ $locationHistory->total() }} entries
            </div>
            <div>{{ $locationHistory->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection
