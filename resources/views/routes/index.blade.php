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
            <div class="module-title-header mb-0">View Routes</div>
            @permission('vehicle-routes', 'create')
                <a href="{{ route('vehicle-routes.create') }}" class="btn btn-primary">Add Route</a>
            @endpermission
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('vehicle-routes.index') }}">
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
                <form method="GET" action="{{ route('vehicle-routes.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search route" style="width: 180px; height: 32px; border-radius: 0;">
                </form>
            </div>
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Route Name</th>
                        <th>Route Code</th>
                        <th>Trip Type</th>
                        <th>Start Location</th>
                        <th>End Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($routes as $route)
                        <tr>
                            <td>{{ $routes->firstItem() + $loop->index }}</td>
                            <td>{{ $route->route_name }}</td>
                            <td>{{ $route->route_code ?: '-' }}</td>
                            <td>{{ $route->trip_type ?: '-' }}</td>
                            <td>{{ $route->start_location ?: '-' }}</td>
                            <td>{{ $route->end_location ?: '-' }}</td>
                            <td>
                                @if ($route->is_active)
                                    <span class="badge badge-success">ACTIVE</span>
                                @else
                                    <span class="badge badge-danger">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('vehicle-routes.show', $route) }}" class="grid-btn-edit"><i class="bi bi-eye"></i>View</a>
                                @permission('vehicle-routes', 'update')
                                    <a href="{{ route('vehicle-routes.edit', $route) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                                @permission('vehicle-routes', 'delete')
                                    <form action="{{ route('vehicle-routes.destroy', $route) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this route?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid-btn-delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3" style="background-color: #fff;">No routes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $routes->count() ? $routes->firstItem() : 0 }} to {{ $routes->count() ? $routes->lastItem() : 0 }} of {{ $routes->total() }} entries
            </div>
            <div>{{ $routes->links('pagination::bootstrap-4') }}</div>
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
