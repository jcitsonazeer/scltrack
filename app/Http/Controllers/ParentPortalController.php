<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\ParentAuthService;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentPortalController extends Controller
{
    public function __construct(
        private SchoolService $schoolService,
        private ParentAuthService $parentAuthService
    ) {
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        $school = $this->school($request);

        if (! $school) {
            return redirect()->route('app.select-school');
        }

        return view('parents.portal_login', ['school' => $school]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_id' => ['required', 'integer'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $school = $this->schoolService->findOpenSchool((int) $validated['school_id']);
        $parent = $this->parentAuthService->login($school, $validated['username'], $validated['password']);

        $request->session()->regenerate();
        $request->session()->put('selected_school_id', $school->id);
        $request->session()->put('parent_id', $parent->id);

        return redirect()->route('app.parent.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $school = $this->school($request);

        if (! $school) {
            return redirect()->route('app.select-school');
        }

        $parentId = $request->session()->get('parent_id');

        if (! $parentId) {
            return redirect()->route('app.parent.login');
        }

        $this->parentAuthService->findOpenSchool($school);

        $parent = $this->parentAuthService->findById((int) $parentId);

        return view('parents.portal_dashboard', [
            'school' => $school,
            'parent' => $this->parentAuthService->profile($school, $parent),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('parent_id');

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
