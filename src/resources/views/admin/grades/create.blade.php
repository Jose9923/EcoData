@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Nuevo grado</h1>
        <p class="mb-0 text-light-emphasis">Configura el grado, su etiqueta visible y el colegio al que pertenece.</p>
    </section>

    <form method="POST" action="{{ route('admin.grades.store') }}">
        @csrf
        @include('admin.grades.partials.form', ['buttonText' => 'Crear grado'])
    </form>
</div>
@endsection