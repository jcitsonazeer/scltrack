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
            <div class="module-title-header mb-0">View Student Route Assignments</div>
            @permission('student-route-assignments', 'create')
                <a href="{{ route('student-route-assignments.create') }}" class="btn btn-primary">Add Assignment</a>
            @endpermission
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('student-route-assignments.index') }}">
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
                <form method="GET" action="{{ route('student-route-assignments.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search assignment" style="width: 180px; height: 32px; border-radius: 0;">
                </form>
            </div>
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Route</th>
                        <th>Stop</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assignments as $assignment)
                        <tr>
                            <td>{{ $assignments->firstItem() + $loop->index }}</td>
                            <td>{{ $assignment->student?->full_name ?: '-' }}</td>
                            <td>{{ $assignment->route?->display_name ?: '-' }}</td>
                            <td>{{ $assignment->stop?->stop_name ?: '-' }}</td>
                            <td>{{ $assignment->assigned_from ? $assignment->assigned_from->format('d-m-Y') : '-' }}</td>
                            <td>{{ $assignment->assigned_to ? $assignment->assigned_to->format('d-m-Y') : '-' }}</td>
                            <td>
                                @if ($assignment->is_active)
                                    <span class="badge badge-success">ACTIVE</span>
                                @else
                                    <span class="badge badge-danger">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('student-route-assignments.show', $assignment) }}" class="grid-btn-edit"><i class="bi bi-eye"></i>View</a>
                                @permission('student-route-assignments', 'update')
                                    <a href="{{ route('student-route-assignments.edit', $assignment) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                                @permission('student-route-assignments', 'delete')
                                    <form action="{{ route('student-route-assignments.destroy', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this assignment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid-btn-delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3" style="background-color: #fff;">No student route assignments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $assignments->count() ? $assignments->firstItem() : 0 }} to {{ $assignments->count() ? $assignments->lastItem() : 0 }} of {{ $assignments->total() }} entries
            </div>
            <div>{{ $assignments->links('pagination::bootstrap-4') }}</div>
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
