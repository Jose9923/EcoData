@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Editar usuario</h1>
        <p class="mb-0 text-light-emphasis">Actualiza los datos, rol y asignación académica del usuario.</p>
    </section>

    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
        @csrf
        @method('PUT')
        @include('admin.users.partials.form', ['user' => $user, 'buttonText' => 'Guardar cambios'])
    </form>
</div>
@endsection