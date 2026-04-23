@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Nuevo colegio</h1>
        <p class="mb-0 text-light-emphasis">Configura datos básicos y colores institucionales.</p>
    </section>

    <form method="POST" action="{{ route('admin.schools.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.schools.partials.form', ['buttonText' => 'Crear colegio'])
    </form>
</div>
@endsection