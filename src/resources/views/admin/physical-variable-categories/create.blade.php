@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Nueva categoría</h1>
        <p class="mb-0 text-light-emphasis">Configura la categoría y su organización dentro del sistema.</p>
    </section>

    <form method="POST" action="{{ route('admin.physical-variable-categories.store') }}">
        @csrf
        @include('admin.physical-variable-categories.partials.form', ['buttonText' => 'Crear categoría'])
    </form>
</div>
@endsection