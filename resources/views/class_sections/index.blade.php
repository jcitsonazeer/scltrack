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
            <div class="module-title-header mb-0">View Class / Sections</div>
            @permission('class-sections', 'create')
                <a href="{{ route('class-sections.create') }}" class="btn btn-primary">Add Class / Section</a>
            @endpermission
        </div>

        <div class="row align-items-center justify-content-between g-2 mb-3">
            <div class="col-auto d-flex align-items-center gap-2">
                <span>Show</span>
                <form method="GET" action="{{ route('class-sections.index') }}">
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
                <form method="GET" action="{{ route('class-sections.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search class" style="width: 180px; height: 32px; border-radius: 0;">
                </form>
            </div>
        </div>

        <div class="table-responsive-wrapper">
            <table class="custom-data-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classSections as $classSection)
                        <tr>
                            <td>{{ $classSections->firstItem() + $loop->index }}</td>
                            <td>{{ $classSection->class_name }}</td>
                            <td>{{ $classSection->section ?: '-' }}</td>
                            <td>
                                @if ($classSection->is_active)
                                    <span class="badge badge-success">ACTIVE</span>
                                @else
                                    <span class="badge badge-danger">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('class-sections.show', $classSection) }}" class="grid-btn-edit"><i class="bi bi-eye"></i>View</a>
                                @permission('class-sections', 'update')
                                    <a href="{{ route('class-sections.edit', $classSection) }}" class="grid-btn-edit"><i class="bi bi-pencil-square"></i>Edit</a>
                                @endpermission
                                @permission('class-sections', 'delete')
                                    <form action="{{ route('class-sections.destroy', $classSection) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this class / section?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid-btn-delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endpermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3" style="background-color: #fff;">No class / sections found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid-footer-pagination-strip">
            <div class="pagination-info-text">
                Showing {{ $classSections->count() ? $classSections->firstItem() : 0 }} to {{ $classSections->count() ? $classSections->lastItem() : 0 }} of {{ $classSections->total() }} entries
            </div>
            <div>{{ $classSections->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection
