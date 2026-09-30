<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ParentModel;
use App\Services\ParentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function __construct(private ParentService $parentService)
    {
    }

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [5, 10, 25, 50], true)) {
            $perPage = 10;
        }

        $parents = $this->parentService->getPaginatedParents($search, $perPage);

        return view('parents.index', [
            'parents' => $parents,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('parents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeCredentials($request);

        $validated = $request->validate($this->rules());
        $validated['created_by_id'] = $this->currentAdminAndDriverId($request);

        $this->parentService->createParent($validated);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent created successfully.');
    }

    public function show(ParentModel $parent): View
    {
        $parent->load(['createdBy', 'updatedBy']);

        return view('parents.show', [
            'parent' => $parent,
        ]);
    }

    public function edit(ParentModel $parent): View
    {
        return view('parents.edit', [
            'parent' => $parent,
        ]);
    }

    public function update(Request $request, ParentModel $parent): RedirectResponse
    {
        $this->normalizeCredentials($request);

        $validated = $request->validate($this->rules($parent->id));
        $validated['updated_by_id'] = $this->currentAdminAndDriverId($request);

        $this->parentService->updateParent($parent, $validated);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent updated successfully.');
    }

    public function destroy(ParentModel $parent): RedirectResponse
    {
        $this->parentService->deleteParent($parent);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent deleted successfully.');
    }

    private function rules(?int $parentId = null): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9]+$/',
                Rule::unique('tenant.parents', 'phone')->ignore($parentId),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenant.parents', 'username')->ignore($parentId),
            ],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function normalizeCredentials(Request $request): void
    {
        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        $request->merge([
            'username' => $username === '' ? null : $username,
            'password' => trim($password) === '' ? null : $password,
        ]);
    }
}
