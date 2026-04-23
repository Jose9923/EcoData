@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Editar categoría</h1>
        <p class="mb-0 text-light-emphasis">Actualiza la información y disponibilidad de la categoría.</p>
    </section>

    <form method="POST" action="{{ route('admin.physical-variable-categories.update', $category->id) }}">
        @csrf
        @method('PUT')
        @include('admin.physical-variable-categories.partials.form', ['category' => $category, 'buttonText' => 'Guardar cambios'])
    </form>
</div>
@endsection