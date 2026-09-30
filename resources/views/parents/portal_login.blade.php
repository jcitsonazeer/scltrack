<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parent Login - Tracking</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-theme">
    <div class="login-page-shell min-vh-100 d-flex align-items-center justify-content-center px-3">
        <div class="login-card">
            <div class="login-brand-mark"></div>
            <div class="module-title-header mb-1">Parent Login</div>
            <div class="login-subtitle mb-3">{{ $school->school_name }}</div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('app.parent.login.store') }}">
                @csrf
                <input type="hidden" name="school_id" value="{{ $school->id }}">
                <div class="mb-3">
                    <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="{{ old('username') }}" autocomplete="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <div class="d-grid gap-2 mt-3">
                <a href="{{ route('app.select-school') }}" class="btn btn-secondary">Change School</a>
            </div>
        </div>
    </div>
</body>
</html>
