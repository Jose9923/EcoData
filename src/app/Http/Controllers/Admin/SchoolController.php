<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSchoolRequest;
use App\Http\Requests\Admin\UpdateSchoolRequest;
use App\Repositories\Contracts\SchoolRepositoryInterface;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function __construct(
        protected SchoolRepositoryInterface $schoolRepository,
        protected SchoolService $schoolService
    ) {
    }

    public function index(Request $request): View
    {
        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $schools = $this->schoolRepository
            ->paginateWithFilters($search, $perPage)
            ->appends($request->query());

        return view('admin.schools.index', compact('schools', 'search', 'perPage'));
    }

    public function create(): View
    {
        return view('admin.schools.create');
    }

    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->schoolService->createSchool($data, $request->file('shield'));

        return redirect()
            ->route('admin.schools.index')
            ->with('success', 'Colegio creado correctamente.');
    }

    public function edit(int $school): View
    {
        $school = $this->schoolRepository->findById($school);

        abort_if(! $school, 404);

        return view('admin.schools.edit', compact('school'));
    }

    public function update(UpdateSchoolRequest $request, int $school): RedirectResponse
    {
        $school = $this->schoolRepository->findById($school);

        abort_if(! $school, 404);

        $this->schoolService->updateSchool($school, $request->validated(), $request->file('shield'));

        return redirect()
            ->route('admin.schools.index')
            ->with('success', 'Colegio actualizado correctamente.');
    }

    public function destroy(int $school): RedirectResponse
    {
        $school = $this->schoolRepository->findById($school);

        abort_if(! $school, 404);

        $this->schoolService->deleteSchool($school);

        return redirect()
            ->route('admin.schools.index')
            ->with('success', 'Colegio eliminado correctamente.');
    }
}