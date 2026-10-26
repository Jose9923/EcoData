@extends('layouts.app')
@section('content')
<div class="admin-card bg-white p-4">
    <h1 class="h3">Reporte de mediciones por correo</h1>
    <p class="text-secondary">Recibirás un archivo Excel en tu correo verificado: <strong>{{ auth()->user()->email }}</strong>. Se aplican los mismos permisos que en la consulta de registros.</p>
    <form method="GET" action="{{ route('reports.mail') }}" class="row g-3">
        @include('reports.partials.filters')
        <div class="col-12"><button class="btn btn-outline-primary">Consultar registros</button></div>
    </form>
    <div class="alert alert-info mt-4">{{ number_format($count) }} registros en el período. Máximo por correo: 2500.</div>
    <form method="POST" action="{{ route('reports.mail.send') }}">
        @csrf
        @foreach($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
        <button class="btn btn-primary" @disabled($count === 0 || $count > 2500)>Enviar a mi correo</button>
        <a href="{{ route('analytics.index', $filters) }}" class="btn btn-outline-secondary">Ver evolución</a>
    </form>
</div>
@endsection
