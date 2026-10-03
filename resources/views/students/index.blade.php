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
            <div class="module-title-header mb-0">View Students</div>
            @permission('students', 'create')
                <a href="{{ route('students.create') }}" class="btn btn-primary">Add Student</a>
            @endpermission
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('students.index') }}">
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
                <form method="GET" action="{{ route('students.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search student" style="width: 180px; height: 32px; border-radius: 0;">
                </form>
            </div>
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Admission No</th>
                        <th>Full Name</th>
                        <th>Parent</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>{{ $students->firstItem() + $loop->index }}</td>
                            <td>{{ $student->admission_no ?: '-' }}</td>
                            <td>{{ $student->full_name }}</td>
                            <td>{{ $student->parent?->full_name ?: '-' }}</td>
                            <td>{{ $student->classSection?->class_name ?: $student->class_name ?: '-' }}</td>
                            <td>{{ $student->classSection?->section ?: $student->section ?: '-' }}</td>
                            <td>
                                @if ($student->is_active)
                                    <span class="badge badge-success">ACTIVE</span>
                                @else
                                    <span class="badge badge-danger">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('students.show', $student) }}" class="grid-btn-edit"><i class="bi bi-eye"></i>View</a>
                                @permission('students', 'update')
                                    <a href="{{ route('students.edit', $student) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                                @permission('students', 'delete')
                                    <form action="{{ route('students.destroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this student?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid-btn-delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3" style="background-color: #fff;">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $students->count() ? $students->firstItem() : 0 }} to {{ $students->count() ? $students->lastItem() : 0 }} of {{ $students->total() }} entries
            </div>
            <div>{{ $students->links('pagination::bootstrap-4') }}</div>
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
