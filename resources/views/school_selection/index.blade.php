<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select School - Tracking</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-theme">
    <div class="login-page-shell min-vh-100 d-flex align-items-center justify-content-center px-3">
        <div class="login-card">
            <div class="login-brand-mark"></div>
            <div class="module-title-header mb-1">Select School</div>
            <div class="login-subtitle mb-3">Choose your school to continue</div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('app.select-school.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label mb-1 text-muted" style="font-size: 0.82rem; font-weight: 500;">School <span class="text-danger">*</span></label>
                    <select name="school_id" class="form-select" required autofocus>
                        <option value="">-- Select School --</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school['id'] }}" {{ (int) old('school_id') === $school['id'] ? 'selected' : '' }}>
                                {{ $school['school_name'] }} {{ $school['school_code'] ? '(' . $school['school_code'] . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Continue</button>
            </form>
        </div>
    </div>
</body>
</html>
