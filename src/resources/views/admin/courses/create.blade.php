@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Nuevo curso</h1>
        <p class="mb-0 text-light-emphasis">Configura el curso, su etiqueta visible y el grado al que pertenece.</p>
    </section>

    <form method="POST" action="{{ route('admin.courses.store') }}">
        @csrf
        @include('admin.courses.partials.form', ['buttonText' => 'Crear curso'])
    </form>
</div>
@endsection