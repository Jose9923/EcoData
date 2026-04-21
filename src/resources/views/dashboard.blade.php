<x-layouts.app>
    <x-slot name="header">Dashboard</x-slot>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">Usuarios</p>
            <h3 class="text-3xl font-bold">120</h3>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">Registros</p>
            <h3 class="text-3xl font-bold">580</h3>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">Pendientes</p>
            <h3 class="text-3xl font-bold">14</h3>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-lg font-semibold mb-4">Resumen general</h2>
        <p class="text-gray-600">Aquí puedes poner tabla, filtros o gráficas.</p>
    </div>
</x-layouts.app>