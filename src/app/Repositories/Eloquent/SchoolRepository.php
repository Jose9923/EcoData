<?php

namespace App\Repositories\Eloquent;

use App\Models\School;
use App\Repositories\Contracts\SchoolRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SchoolRepository implements SchoolRepositoryInterface
{
    public function paginateWithFilters(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return School::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?School
    {
        return School::find($id);
    }

    public function create(array $data): School
    {
        return School::create($data);
    }

    public function update(School $school, array $data): School
    {
        $school->update($data);

        return $school->refresh();
    }

    public function delete(School $school): bool
    {
        return (bool) $school->delete();
    }
}