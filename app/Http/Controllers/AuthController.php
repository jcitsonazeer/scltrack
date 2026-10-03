<?php

namespace App\Http\Controllers;

use App\Models\Tenant\AdminAndDriver;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService
    ) {
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('admin_and_driver_id')) {
            return redirect()->route($this->defaultRouteName());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = AdminAndDriver::query()
            ->where('username', trim($credentials['username']))
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['username' => 'Invalid username or password.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_and_driver_id', $user->id);

        return redirect()->intended(route($this->defaultRouteName($user)));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_and_driver_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * A driver has no parents module, so it lands on the stop list it is
     * allowed to open. Everyone else keeps the parents list as before.
     */
    private function defaultRouteName(?AdminAndDriver $user = null): string
    {
        $user ??= $this->rolePermissionService->currentUser();

        if ($this->rolePermissionService->isDriver($user)) {
            return 'stops.index';
        }

        return 'parents.index';
    }
}
