@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Editar guía de laboratorio</h1>
        <p class="mb-0 text-light-emphasis">Actualiza la guía y su PDF.</p>
    </section>

    <form method="POST" action="{{ route('admin.laboratory-guides.update', $guide) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.laboratory-guides.partials.form', ['guide' => $guide, 'buttonText' => 'Guardar cambios'])
    </form>
</div>
@endsection