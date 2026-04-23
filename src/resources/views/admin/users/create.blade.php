@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Nuevo usuario</h1>
        <p class="mb-0 text-light-emphasis">Configura identidad, acceso y asignación académica.</p>
    </section>

    <form method="GET" action="{{ route('admin.users.create') }}" class="d-none" id="user-filter-form"></form>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        @include('admin.users.partials.form', ['buttonText' => 'Crear usuario'])
    </form>
</div>
@endsection