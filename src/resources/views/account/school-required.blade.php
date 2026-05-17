@extends('layouts.app')

@section('content')
    <div class="min-vh-100 d-flex align-items-center justify-content-center p-4">
        <div class="admin-card bg-white p-4 p-md-5 text-center" style="max-width: 720px;">
            <div class="mb-4">
                <h1 class="display-6 fw-bold mb-2">
                    Colegio no asignado
                </h1>

                <p class="text-muted mb-0">
                    Tu usuario fue autenticado correctamente, pero no tiene un colegio asignado.
                    Para acceder a EcoData, un administrador debe vincular tu cuenta a una institución educativa.
                </p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                        style="background-color: var(--school-primary);">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>

    <div class="modal fade"
         id="schoolRequiredModal"
         tabindex="-1"
         aria-labelledby="schoolRequiredModalLabel"
         aria-hidden="true"
         data-bs-backdrop="static"
         data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" id="schoolRequiredModalLabel">
                            Colegio no asignado
                        </h5>
                        <p class="text-muted small mb-0">
                            No puedes continuar en EcoData.
                        </p>
                    </div>
                </div>

                <div class="modal-body pt-4">
                    <p class="mb-3">
                        Tu cuenta no tiene un colegio asociado. Esto impide cargar correctamente los módulos de estudiantes,
                        docentes, registros físicos, guías, diario de campo y calendario ambiental.
                    </p>

                    <div class="rounded-4 p-3"
                         style="background-color: #fff8e5; border: 1px solid #ffe4a3;">
                        <div class="fw-semibold mb-1" style="color: #7a5200;">
                            Acción requerida
                        </div>
                        <p class="small mb-0" style="color: #7a5200; line-height: 1.45;">
                            Solicita al administrador que edite tu usuario y te asigne el colegio correspondiente.
                        </p>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <form id="logoutSchoolRequiredForm" method="POST" action="{{ route('logout') }}" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'warning',
                title: 'Colegio no asignado',
                text: 'Tu cuenta no tiene un colegio asociado. Solicita al administrador que edite tu usuario y te asigne el colegio correspondiente.',
                confirmButtonText: 'Entendido, cerrar sesión',
                allowOutsideClick: false,
                allowEscapeKey: false,
                confirmButtonColor: getComputedStyle(document.documentElement)
                    .getPropertyValue('--school-primary')
                    .trim(),
                customClass: {
                    popup: 'rounded-4',
                    confirmButton: 'rounded-4 px-4 fw-semibold'
                }
            }).then(function () {
                document.getElementById('logoutSchoolRequiredForm').submit();
            });
        });
    </script>
@endpush