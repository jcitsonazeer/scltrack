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
            <div class="module-title-header mb-0">View Stops</div>
            @permission('stops', 'create')
                <a href="{{ route('stops.create') }}" class="btn btn-primary">Add Stop</a>
            @endpermission
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('stops.index') }}">
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
                <form method="GET" action="{{ route('stops.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search stop" style="width: 180px; height: 32px; border-radius: 0;">
                </form>
            </div>
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Stop Name</th>
                        <th>Stop Code</th>
                        <th>Route</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stops as $stop)
                        <tr>
                            <td>{{ $stops->firstItem() + $loop->index }}</td>
                            <td>{{ $stop->stop_name }}</td>
                            <td>{{ $stop->stop_code ?: '-' }}</td>
                            <td>{{ $stop->route?->display_name ?: '-' }}</td>
                            <td>{{ $stop->latitude !== null ? $stop->latitude : '-' }}</td>
                            <td>{{ $stop->longitude !== null ? $stop->longitude : '-' }}</td>
                            <td>{{ $stop->stop_order }}</td>
                            <td>
                                @if ($stop->is_active)
                                    <span class="badge badge-success">ACTIVE</span>
                                @else
                                    <span class="badge badge-danger">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('stops.show', $stop) }}" class="grid-btn-edit"><i class="bi bi-eye"></i>View</a>
                                @permission('stops', 'update')
                                    <a href="{{ route('stops.edit', $stop) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                                @permission('stops', 'delete')
                                    <form action="{{ route('stops.destroy', $stop) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this stop?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid-btn-delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3" style="background-color: #fff;">No stops found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $stops->count() ? $stops->firstItem() : 0 }} to {{ $stops->count() ? $stops->lastItem() : 0 }} of {{ $stops->total() }} entries
            </div>
            <div>{{ $stops->links('pagination::bootstrap-4') }}</div>
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
