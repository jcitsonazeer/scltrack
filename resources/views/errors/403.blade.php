<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied - Tracking</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrapicons.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <div class="top-navbar">
        <a href="{{ route('dashboard.index') }}" class="navbar-brand-title">TRACKING</a>
    </div>

    <div class="page-content-body">
        <div class="module-card text-center">
            <div class="mb-2" style="font-size: 3rem; font-weight: 800; color: var(--blue);">403</div>
            <div class="module-title-header mb-2">Access Denied</div>
            <p class="text-muted mb-4">
                {{ $exception->getMessage() ?: 'You do not have permission to open this page.' }}
            </p>
            <a href="{{ route('dashboard.index') }}" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>

    <div class="system-footer-bar">
        <span>&copy; 2026 <strong class="text-primary text-decoration-none">School Bus Tracking</strong></span>
        <span>All Rights Reserved</span>
    </div>
</body>
</html>