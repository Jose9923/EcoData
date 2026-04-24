@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Editar registro físico</h1>
        <p class="mb-0 text-light-emphasis">
            Corrige los valores capturados y actualiza el contexto del registro si es necesario.
        </p>
    </section>

    <form method="POST" action="{{ route('admin.physical-variable-records.update', $record->id) }}">
        @csrf
        @method('PUT')
        @include('admin.physical-variable-records.partials.form', ['record' => $record, 'buttonText' => 'Guardar cambios'])
    </form>
</div>
@endsection