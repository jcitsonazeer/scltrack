<?php

namespace App\Services;

use App\Models\Tenant\ParentModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ParentService
{
    public function getPaginatedParents(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return ParentModel::query()
            ->when($search, function ($query, $searchText) {
                $query->where('full_name', 'like', '%' . $searchText . '%')
                    ->orWhere('phone', 'like', '%' . $searchText . '%')
                    ->orWhere('email', 'like', '%' . $searchText . '%');
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createParent(array $data): ParentModel
    {
        return ParentModel::create($this->prepareData($data));
    }

    public function updateParent(ParentModel $parent, array $data): ParentModel
    {
        $parent->update($this->prepareData($data));

        return $parent->fresh();
    }

    public function deleteParent(ParentModel $parent): void
    {
        if ($parent->students()->exists()) {
            throw ValidationException::withMessages([
                'parent' => 'This parent is added to the student..first delete the student and then delete the parent',
            ]);
        }

        $parent->delete();
    }

    public function prepareData(array $data): array
    {
        $prepared = [
            'full_name' => $data['full_name'],
            'phone' => trim($data['phone']),
            'email' => $data['email'] ?: null,
            'address' => $data['address'] ?: null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?: null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?: null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'updated_by_id' => $data['updated_by_id'] ?? null,
        ];

        if (array_key_exists('created_by_id', $data)) {
            $prepared['created_by_id'] = $data['created_by_id'];
        }

        if (array_key_exists('username', $data)) {
            $prepared['username'] = $data['username'] ?: null;
        }

        if (! empty($data['password'])) {
            $prepared['password'] = Hash::make($data['password']);
        }

        return $prepared;
    }
}
