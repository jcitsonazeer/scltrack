@extends('layouts.headerfooter')

@section('content')
<div class="module-card">
    <div class="module-title-header mb-3">Edit Parent</div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('parents.update', $parent) }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $parent->full_name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Phone <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control js-number-only" value="{{ old('phone', $parent->phone) }}" inputmode="numeric" pattern="[0-9]+" required>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $parent->email) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Username</label>
                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $parent->username) }}" maxlength="255" autocomplete="username">
                <div class="form-text" style="font-size: 0.78rem;">Clear this to remove the username.</div>
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                <div class="form-text" style="font-size: 0.78rem;">Leave blank to keep the current password. Minimum 6 characters.</div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Status <span class="text-danger">*</span></label>
                <select name="is_active" class="form-select" required>
                    <option value="1" {{ old('is_active', $parent->is_active) == 1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('is_active', $parent->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Emergency Contact Name</label>
                <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $parent->emergency_contact_name) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Emergency Contact Phone</label>
                <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $parent->emergency_contact_phone) }}">
            </div>
            <div class="col-12">
                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Address</label>
                <textarea name="address" class="form-control" rows="4">{{ old('address', $parent->address) }}</textarea>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('parents.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var inputs = document.querySelectorAll('.js-number-only');

    inputs.forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
});
</script>
@endpush
