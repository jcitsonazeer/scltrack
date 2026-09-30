<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolSelectionController extends Controller
{
    public function __construct(private SchoolService $schoolService)
    {
    }

    public function index(): View
    {
        $schools = $this->schoolService->getSelectableSchools();

        return view('school_selection.index', [
            'schools' => $this->schoolService->schoolList($schools),
        ]);
    }

    public function select(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_id' => ['required', 'integer'],
        ]);

        $school = $this->schoolService->findOpenSchool((int) $validated['school_id']);

        $request->session()->put('selected_school_id', $school->id);

        return redirect()->route('app.login-choice');
    }

    public function loginChoice(Request $request): View|RedirectResponse
    {
        $school = $this->selectedSchool($request);

        if (! $school) {
            return redirect()->route('app.select-school');
        }

        return view('school_selection.choose_login', [
            'school' => $school,
        ]);
    }

    public function selectedSchool(Request $request): ?School
    {
        $schoolId = $request->session()->get('selected_school_id');

        if (! $schoolId) {
            return null;
        }

        return School::query()->find($schoolId);
    }
}
