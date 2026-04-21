<?php

namespace App\Repositories\Contracts;

use App\Models\School;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SchoolRepositoryInterface
{
    public function paginateWithFilters(string $search = '', int $perPage = 10): LengthAwarePaginator;
    public function findById(int $id): ?School;
    public function create(array $data): School;
    public function update(School $school, array $data): School;
    public function delete(School $school): bool;
}