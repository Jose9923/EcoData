@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Nueva guía de laboratorio</h1>
        <p class="mb-0 text-light-emphasis">Carga una guía en PDF para tus estudiantes.</p>
    </section>

    <form method="POST" action="{{ route('admin.laboratory-guides.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.laboratory-guides.partials.form', ['buttonText' => 'Guardar guía'])
    </form>
</div>
@endsection