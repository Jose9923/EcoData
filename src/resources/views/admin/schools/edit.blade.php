@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Editar colegio</h1>
        <p class="mb-0 text-light-emphasis">Actualiza información, branding y estado.</p>
    </section>

    <form method="POST" action="{{ route('admin.schools.update', $school->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.schools.partials.form', ['school' => $school, 'buttonText' => 'Guardar cambios'])
    </form>
</div>
@endsection