@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Editar grado</h1>
        <p class="mb-0 text-light-emphasis">Actualiza la información y disponibilidad del grado.</p>
    </section>

    <form method="POST" action="{{ route('admin.grades.update', $grade->id) }}">
        @csrf
        @method('PUT')
        @include('admin.grades.partials.form', ['grade' => $grade, 'buttonText' => 'Guardar cambios'])
    </form>
</div>
@endsection