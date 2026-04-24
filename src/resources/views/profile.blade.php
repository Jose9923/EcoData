@extends('layouts.app')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="admin-hero p-4 p-md-5">
        <h1 class="h2 fw-bold mb-2">Mi perfil</h1>
        <p class="mb-0 text-light-emphasis">
            Gestiona tu información personal y la seguridad de tu cuenta.
        </p>
    </section>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="admin-card bg-white p-4 h-100">
                <h5 class="fw-bold mb-3">Resumen</h5>

                <div class="small text-secondary d-flex flex-column gap-2">
                    <div><strong>Nombre:</strong> {{ $user->name }}</div>
                    <div><strong>Correo:</strong> {{ $user->email }}</div>
                    <div><strong>Tipo de identificación:</strong> {{ $user->document_type ?? 'Sin asignar' }}</div>
                    <div><strong>Número de identificación:</strong> {{ $user->document_number ?? 'Sin asignar' }}</div>
                    <div><strong>Rol:</strong> {{ \Illuminate\Support\Str::title($user->roles->first()?->name ?? 'Sin rol') }}</div>
                    <div><strong>Colegio:</strong> {{ $user->school?->name ?? 'Sin asignar' }}</div>
                    <div><strong>Grado:</strong> {{ $user->grade?->label ?: $user->grade?->name ?: 'Sin asignar' }}</div>
                    <div><strong>Curso:</strong> {{ $user->course?->label ?: $user->course?->name ?: 'Sin asignar' }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="d-flex flex-column gap-4">
                <div class="admin-card bg-white p-4">
                    <h5 class="fw-bold mb-3">Actualizar perfil</h5>

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Nombre</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="form-control rounded-4 @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}"
                                >
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Tipo de identificación</label>
                                <select
                                    name="document_type"
                                    class="form-select rounded-4 @error('document_type') is-invalid @enderror"
                                >
                                    <option value="">Selecciona</option>
                                    <option value="CC" @selected(old('document_type', $user->document_type) === 'CC')>CC</option>
                                    <option value="TI" @selected(old('document_type', $user->document_type) === 'TI')>TI</option>
                                    <option value="CE" @selected(old('document_type', $user->document_type) === 'CE')>CE</option>
                                    <option value="PPT" @selected(old('document_type', $user->document_type) === 'PPT')>PPT</option>
                                    <option value="NIT" @selected(old('document_type', $user->document_type) === 'NIT')>NIT</option>
                                    <option value="PAS" @selected(old('document_type', $user->document_type) === 'PAS')>PAS</option>
                                </select>
                                @error('document_type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Número de identificación</label>
                                <input
                                    type="text"
                                    name="document_number"
                                    class="form-control rounded-4 @error('document_number') is-invalid @enderror"
                                    value="{{ old('document_number', $user->document_number) }}"
                                >
                                @error('document_number')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Correo electrónico</label>
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control rounded-4 @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}"
                                >
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn text-white rounded-4 px-4"
                                    style="background-color: var(--school-primary);">
                                Guardar perfil
                            </button>
                        </div>
                    </form>
                </div>

                <div class="admin-card bg-white p-4">
                    <h5 class="fw-bold mb-3">Cambiar contraseña</h5>

                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Contraseña actual</label>
                                <input
                                    type="password"
                                    name="current_password"
                                    class="form-control rounded-4 @error('current_password') is-invalid @enderror"
                                >
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Nueva contraseña</label>
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control rounded-4 @error('password') is-invalid @enderror"
                                >
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Confirmar nueva contraseña</label>
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    class="form-control rounded-4"
                                >
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-dark rounded-4 px-4">
                                Actualizar contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection