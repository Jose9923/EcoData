@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Nuevo registro físico</h1>
        <p class="mb-0 text-light-emphasis">
            Captura variables físicas por contexto académico, categoría y fecha de medición.
        </p>
    </section>

    <form method="POST" action="{{ route('admin.physical-variable-records.store') }}">
        @csrf
        @include('admin.physical-variable-records.partials.form', ['buttonText' => 'Guardar registro'])
    </form>
</div>
@endsection