@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Nueva variable física</h1>
        <p class="mb-0 text-light-emphasis">Configura la variable, su unidad y reglas de validación.</p>
    </section>

    <form method="POST" action="{{ route('admin.physical-variables.store') }}">
        @csrf
        @include('admin.physical-variables.partials.form', ['buttonText' => 'Crear variable'])
    </form>
</div>
@endsection