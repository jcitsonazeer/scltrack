<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\DriverAuthService;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverPortalController extends Controller
{
    public function __construct(
        private SchoolService $schoolService,
        private DriverAuthService $driverAuthService
    ) {
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        $school = $this->school($request);

        if (! $school) {
            return redirect()->route('app.select-school');
        }

        return view('admin_and_drivers.portal_login', ['school' => $school]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_id' => ['required', 'integer'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $school = $this->schoolService->findOpenSchool((int) $validated['school_id']);
        $user = $this->driverAuthService->login($school, $validated['username'], $validated['password']);

        $request->session()->regenerate();
        $request->session()->put('selected_school_id', $school->id);
        $request->session()->put('portal_admin_and_driver_id', $user->id);

        return redirect()->route('app.driver.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $school = $this->school($request);

        if (! $school) {
            return redirect()->route('app.select-school');
        }

        $userId = $request->session()->get('portal_admin_and_driver_id');

        if (! $userId) {
            return redirect()->route('app.driver.login');
        }

        $this->driverAuthService->findOpenSchool($school);

        $user = $this->driverAuthService->findById((int) $userId);

        return view('admin_and_drivers.portal_dashboard', [
            'school' => $school,
            'user' => $this->driverAuthService->profile($school, $user),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('portal_admin_and_driver_id');

        return redirect()->route('app.select-school');
    }

    private function school(Request $request): ?School
    {
        $schoolId = $request->session()->get('selected_school_id');

        if (! $schoolId) {
            return null;
        }

        return School::query()->find($schoolId);
    }
}
