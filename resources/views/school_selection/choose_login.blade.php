<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Login - Tracking</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrapicons.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-theme">
    <div class="login-page-shell min-vh-100 d-flex align-items-center justify-content-center px-3">
        <div class="login-card">
            <div class="login-brand-mark"></div>
            <div class="module-title-header mb-1">{{ $school->school_name }}</div>
            <div class="login-subtitle mb-3">Please choose how you want to login</div>

            <div class="d-grid gap-2">
                <a href="{{ route('app.parent.login') }}" class="btn btn-primary">Parent Login</a>
                <a href="{{ route('app.driver.login') }}" class="btn btn-secondary">Driver / Transport Admin Login</a>
            </div>

            <div class="d-grid gap-2 mt-3">
                <a href="{{ route('app.select-school') }}" class="btn btn-secondary">Change School</a>
            </div>
        </div>
    </div>
</body>
</html>
