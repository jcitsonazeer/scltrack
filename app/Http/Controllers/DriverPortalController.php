<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Tenant\AdminAndDriver;
use App\Services\DriverAuthService;
use App\Services\DriverStopService;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverPortalController extends Controller
{
    public function __construct(
        private SchoolService $schoolService,
        private DriverAuthService $driverAuthService,
        private DriverStopService $driverStopService
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

    public function stops(Request $request): View|RedirectResponse
    {
        $portal = $this->portalUser($request);

        if ($portal instanceof RedirectResponse) {
            return $portal;
        }

        [$school, $user] = $portal;

        return view('admin_and_drivers.portal_stops', [
            'school' => $school,
            'user' => $this->driverAuthService->profile($school, $user),
            'routes' => $this->driverStopService->assignedRoutes($user->id),
            'stops' => $this->driverStopService->stopsForDriver($user->id),
        ]);
    }

    public function storeStop(Request $request): RedirectResponse
    {
        $portal = $this->portalUser($request);

        if ($portal instanceof RedirectResponse) {
            return $portal;
        }

        [, $user] = $portal;

        $validated = $request->validate([
            'route_id' => ['required', 'integer'],
            'stop_name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'stop_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->driverStopService->createStop($validated, $user->id);

        return redirect()
            ->route('app.driver.stops')
            ->with('success', 'Stop added at your current location.');
    }

    public function updateStopLocation(Request $request, int $stopId): RedirectResponse
    {
        $portal = $this->portalUser($request);

        if ($portal instanceof RedirectResponse) {
            return $portal;
        }

        [, $user] = $portal;

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $stop = $this->driverStopService->findStop($stopId);

        $this->driverStopService->updateStopLocation($stop, $validated, $user->id);

        return redirect()
            ->route('app.driver.stops')
            ->with('success', 'Stop location updated.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('portal_admin_and_driver_id');

        return redirect()->route('app.select-school');
    }

    /**
     * The logged in portal user, or a redirect when the session is missing.
     *
     * @return array{0: School, 1: AdminAndDriver}|RedirectResponse
     */
    private function portalUser(Request $request): array|RedirectResponse
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

        if ($user->user_role !== 'cab drivers') {
            return redirect()
                ->route('app.driver.dashboard')
                ->with('error', 'Only cab drivers can update stop locations.');
        }

        return [$school, $user];
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
