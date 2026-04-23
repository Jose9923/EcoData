@extends('layouts.app')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="admin-card bg-white p-4 h-100">
                <p class="text-secondary mb-2">Usuarios</p>
                <h3 class="display-6 fw-bold mb-0">120</h3>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="admin-card bg-white p-4 h-100">
                <p class="text-secondary mb-2">Registros</p>
                <h3 class="display-6 fw-bold mb-0">580</h3>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="admin-card bg-white p-4 h-100">
                <p class="text-secondary mb-2">Pendientes</p>
                <h3 class="display-6 fw-bold mb-0">14</h3>
            </div>
        </div>
    </div>

    <div class="admin-card bg-white p-4">
        <h2 class="h5 fw-semibold mb-3">Resumen general</h2>
        <p class="text-secondary mb-0">Aquí puedes poner tabla, filtros o gráficas.</p>
    </div>
@endsection