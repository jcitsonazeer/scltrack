<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ParentModel;
use App\Services\ParentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParentController extends Controller
{
    public function __construct(private ParentService $parentService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [5, 10, 25, 50], true)) {
            $perPage = 10;
        }

        $parents = $this->parentService->getPaginatedParents($search, $perPage);

        return response()->json($parents);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeCredentials($request);

        $validated = $request->validate($this->rules());
        $validated['created_by_id'] = null;

        $parent = $this->parentService->createParent($validated);

        return response()->json([
            'message' => 'Parent created successfully.',
            'data' => $parent,
        ], 201);
    }

    public function show(ParentModel $parent): JsonResponse
    {
        return response()->json($parent);
    }

    public function update(Request $request, ParentModel $parent): JsonResponse
    {
        $this->normalizeCredentials($request);

        $validated = $request->validate($this->rules($parent->id));

        $parent = $this->parentService->updateParent($parent, $validated);

        return response()->json([
            'message' => 'Parent updated successfully.',
            'data' => $parent,
        ]);
    }

    public function destroy(ParentModel $parent): JsonResponse
    {
        $this->parentService->deleteParent($parent);

        return response()->json([
            'message' => 'Parent deleted successfully.',
        ]);
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
