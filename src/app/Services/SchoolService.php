<?php

namespace App\Services;

use App\Models\School;
use App\Repositories\Contracts\SchoolRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolService
{
    public function __construct(
        protected SchoolRepositoryInterface $schoolRepository
    ) {}

    public function createSchool(array $data, ?UploadedFile $shield = null): School
    {
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);

        if ($shield) {
            $data['shield_path'] = $shield->store('schools/shields', 'public');
        }

        return $this->schoolRepository->create($data);
    }

    public function updateSchool(School $school, array $data, ?UploadedFile $shield = null): School
    {
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);

        if ($shield) {
            if ($school->shield_path && Storage::disk('public')->exists($school->shield_path)) {
                Storage::disk('public')->delete($school->shield_path);
            }

            $data['shield_path'] = $shield->store('schools/shields', 'public');
        }

        return $this->schoolRepository->update($school, $data);
    }

    public function deleteSchool(School $school): bool
    {
        if ($school->shield_path && Storage::disk('public')->exists($school->shield_path)) {
            Storage::disk('public')->delete($school->shield_path);
        }

        return $this->schoolRepository->delete($school);
    }
}