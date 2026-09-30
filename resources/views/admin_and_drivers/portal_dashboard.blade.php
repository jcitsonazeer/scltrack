<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Driver / Transport Admin Dashboard - Tracking</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrapicons.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <div class="top-navbar">
        <span class="navbar-brand-title">TRACKING</span>
        <div class="user-profile-block">
            <span>{{ $user['full_name'] }}</span>
            <form method="POST" action="{{ route('app.driver.logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-light">Logout</button>
            </form>
        </div>
    </div>

    <div class="page-content-body">
        <div class="module-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="module-title-header mb-0">Driver / Transport Admin Details</div>
                <span class="badge badge-success">LOGGED IN</span>
            </div>

            <table class="table table-bordered">
                <tr><th>School</th><td>{{ $user['school_name'] }}</td></tr>
                <tr><th>User ID</th><td>{{ $user['id'] }}</td></tr>
                <tr><th>Full Name</th><td>{{ $user['full_name'] }}</td></tr>
                <tr><th>Username</th><td>{{ $user['username'] ?: '-' }}</td></tr>
                <tr><th>User Role</th><td>{{ $user['user_role'] }}</td></tr>
                <tr><th>Phone</th><td>{{ $user['phone'] ?: '-' }}</td></tr>
                <tr><th>Email</th><td>{{ $user['email_id'] ?: '-' }}</td></tr>
                <tr><th>License Number</th><td>{{ $user['license_number'] ?: '-' }}</td></tr>
                <tr><th>License Expiry Date</th><td>{{ $user['license_expiry_date'] ?: '-' }}</td></tr>
                <tr><th>Address</th><td>{{ $user['address'] ?: '-' }}</td></tr>
                <tr><th>Status</th><td>{{ $user['is_active'] ? 'Active' : 'Inactive' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="system-footer-bar">
        <span>&copy; 2026 <strong class="text-primary text-decoration-none">School Bus Tracking</strong></span>
        <span>All Rights Reserved</span>
    </div>
</body>
</html>
