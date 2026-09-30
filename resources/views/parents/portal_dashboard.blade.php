<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parent Dashboard - Tracking</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrapicons.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <div class="top-navbar">
        <span class="navbar-brand-title">TRACKING</span>
        <div class="user-profile-block">
            <span>{{ $parent['full_name'] }}</span>
            <form method="POST" action="{{ route('app.parent.logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-light">Logout</button>
            </form>
        </div>
    </div>

    <div class="page-content-body">
        <div class="module-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="module-title-header mb-0">Parent Details</div>
                <span class="badge badge-success">LOGGED IN</span>
            </div>

            <table class="table table-bordered">
                <tr><th>School</th><td>{{ $parent['school_name'] }}</td></tr>
                <tr><th>Parent ID</th><td>{{ $parent['id'] }}</td></tr>
                <tr><th>Full Name</th><td>{{ $parent['full_name'] }}</td></tr>
                <tr><th>Username</th><td>{{ $parent['username'] ?: '-' }}</td></tr>
                <tr><th>Phone</th><td>{{ $parent['phone'] ?: '-' }}</td></tr>
                <tr><th>Email</th><td>{{ $parent['email'] ?: '-' }}</td></tr>
                <tr><th>Address</th><td>{{ $parent['address'] ?: '-' }}</td></tr>
                <tr><th>Emergency Contact Name</th><td>{{ $parent['emergency_contact_name'] ?: '-' }}</td></tr>
                <tr><th>Emergency Contact Phone</th><td>{{ $parent['emergency_contact_phone'] ?: '-' }}</td></tr>
                <tr><th>Status</th><td>{{ $parent['is_active'] ? 'Active' : 'Inactive' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="system-footer-bar">
        <span>&copy; 2026 <strong class="text-primary text-decoration-none">School Bus Tracking</strong></span>
        <span>All Rights Reserved</span>
    </div>
</body>
</html>
