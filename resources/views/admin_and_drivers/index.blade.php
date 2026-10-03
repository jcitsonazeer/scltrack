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
            <div class="module-title-header mb-0">View Admin & Drivers</div>
            @permission('admin-and-drivers', 'create')
                <a href="{{ route('admin-and-drivers.create') }}" class="btn btn-primary">Add Admin / Driver</a>
            @endpermission
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('admin-and-drivers.index') }}">
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
                <form method="GET" action="{{ route('admin-and-drivers.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search admin or driver" style="width: 220px; height: 32px; border-radius: 0;">
                </form>
            </div>
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>User Role</th>
                        <th>License No</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adminAndDrivers as $adminAndDriver)
                        <tr>
                            <td>{{ $adminAndDrivers->firstItem() + $loop->index }}</td>
                            <td>{{ $adminAndDriver->full_name }}</td>
                            <td>{{ $adminAndDriver->phone ?: '-' }}</td>
                            <td>{{ $adminAndDriver->email_id ?: '-' }}</td>
                            <td>{{ $adminAndDriver->username }}</td>
                            <td>{{ ucwords($adminAndDriver->user_role) }}</td>
                            <td>{{ $adminAndDriver->license_number ?: '-' }}</td>
                            <td>
                                @if ($adminAndDriver->is_active)
                                    <span class="badge badge-success">ACTIVE</span>
                                @else
                                    <span class="badge badge-danger">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('admin-and-drivers.show', $adminAndDriver) }}" class="grid-btn-edit"><i class="bi bi-eye"></i>View</a>
                                @permission('admin-and-drivers', 'update')
                                    <a href="{{ route('admin-and-drivers.edit', $adminAndDriver) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                                @permission('admin-and-drivers', 'delete')
                                    <form action="{{ route('admin-and-drivers.destroy', $adminAndDriver) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this admin / driver?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid-btn-delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3" style="background-color: #fff;">No admin or drivers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $adminAndDrivers->count() ? $adminAndDrivers->firstItem() : 0 }} to {{ $adminAndDrivers->count() ? $adminAndDrivers->lastItem() : 0 }} of {{ $adminAndDrivers->total() }} entries
            </div>
            <div>{{ $adminAndDrivers->links('pagination::bootstrap-4') }}</div>
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
