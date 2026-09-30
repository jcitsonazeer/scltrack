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
            <div class="module-title-header mb-0">View Parents</div>
            <a href="{{ route('parents.create') }}" class="btn btn-primary">Add Parent</a>
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('parents.index') }}">
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
                <form method="GET" action="{{ route('parents.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search parent" style="width: 180px; height: 32px; border-radius: 0;">
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
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parents as $parent)
                        <tr>
                            <td>{{ $parents->firstItem() + $loop->index }}</td>
                            <td>{{ $parent->full_name }}</td>
                            <td>{{ $parent->phone ?: '-' }}</td>
                            <td>{{ $parent->email ?: '-' }}</td>
                            <td>{{ $parent->username ?: '-' }}</td>
                            <td>
                                @if ($parent->is_active)
                                    <span class="badge badge-success">ACTIVE</span>
                                @else
                                    <span class="badge badge-danger">INACTIVE</span>
                                @endif
                            </td>
                            <td>{{ $parent->created_at ? $parent->created_at->format('d-m-Y H:i') : '-' }}</td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('parents.show', $parent) }}" class="grid-btn-edit">
                                    <i class="bi bi-eye"></i>View
                                </a>
                                <a href="{{ route('parents.edit', $parent) }}" class="grid-btn-edit">
                                    <i class="bi bi-pencil-square"></i>Edit
                                </a>
                                <form action="{{ route('parents.destroy', $parent) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this parent?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="grid-btn-delete">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3" style="background-color: #fff;">No parents found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $parents->count() ? $parents->firstItem() : 0 }} to {{ $parents->count() ? $parents->lastItem() : 0 }} of {{ $parents->total() }} entries
            </div>
            <div>{{ $parents->links('pagination::bootstrap-4') }}</div>
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
