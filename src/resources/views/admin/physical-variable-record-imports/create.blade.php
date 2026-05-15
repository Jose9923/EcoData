@extends('layouts.app')

@section('title', 'Cargue CSV de datos meteorológicos')

@section('content')
    <section class="admin-hero p-4 p-md-5">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <div class="text-uppercase small fw-semibold text-light-emphasis mb-2">
                    Administración
                </div>
                <h1 class="display-6 fw-bold mb-2">Cargue CSV de datos meteorológicos</h1>
                <p class="mb-0 text-light-emphasis">
                    Importa de forma masiva los datos capturados por una estación meteorológica EcoData.
                </p>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <a href="{{ route('admin.physical-variable-records.create') }}"
                   class="btn text-white rounded-4 px-4 py-3 fw-semibold"
                   style="background-color: var(--school-primary);">
                    Ver Registros físicos
                </a>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-8">
                <div class="mb-4">
                    <h2 class="h5 fw-bold mb-1">Archivo de datos</h2>
                    <p class="text-muted mb-0">
                        Selecciona la estación meteorológica, carga el archivo CSV y registra los datos físicos
                        capturados por los sensores de la estación.
                    </p>
                </div>

                <form method="POST" action="#" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-4">
                        <div class="col-12">
                            <label for="weather_station_id" class="form-label fw-semibold">
                                Estación meteorológica <span class="text-danger">*</span>
                            </label>
                            <select id="weather_station_id"
                                    name="weather_station_id"
                                    class="form-select form-select-lg rounded-4">
                                <option value="">Selecciona una estación</option>
                                @foreach($weatherStations as $station)
                                    <option value="{{ $station->id }}">
                                        {{ $station->name }} · {{ $station->code }}
                                        @if(auth()->user()->hasRole('super_admin'))
                                            · {{ $station->school?->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Los registros importados quedarán asociados a la estación seleccionada.
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label for="source_type" class="form-label fw-semibold">
                                Origen del dato
                            </label>
                            <input type="text"
                                   id="source_type"
                                   class="form-control form-control-lg rounded-4"
                                   value="Cargue CSV"
                                   disabled>
                            <input type="hidden" name="source_type" value="csv">
                        </div>

                        <div class="col-12 col-lg-6">
                            <label for="delimiter" class="form-label fw-semibold">
                                Separador del archivo
                            </label>
                            <select id="delimiter"
                                    name="delimiter"
                                    class="form-select form-select-lg rounded-4">
                                <option value=",">Coma (,)</option>
                                <option value=";">Punto y coma (;)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="csv_file" class="form-label fw-semibold">
                                Archivo CSV <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                   id="csv_file"
                                   name="csv_file"
                                   accept=".csv,text/csv"
                                   class="form-control form-control-lg rounded-4">
                            <div class="form-text">
                                El archivo debe contener una columna de fecha y columnas de variables meteorológicas.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="observations" class="form-label fw-semibold">
                                Observaciones del cargue
                            </label>
                            <textarea id="observations"
                                      name="observations"
                                      rows="4"
                                      class="form-control form-control-lg rounded-4"
                                      placeholder="Ej. Datos descargados desde la estación principal durante la semana ambiental."></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.physical-variable-records.index') }}"
                           class="btn btn-outline-secondary btn-lg rounded-4 px-4">
                            Cancelar
                        </a>

                        <button type="button"
                                class="btn btn-outline-dark btn-lg rounded-4 px-4">
                            Descargar plantilla CSV
                        </button>

                        <button type="submit"
                                class="btn text-white btn-lg rounded-4 px-4 fw-semibold"
                                style="background-color: var(--school-primary);">
                            Cargar CSV
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-12 col-xl-4">
                <div class="border rounded-4 p-4 h-100">
                    <div class="mb-3">
                        <div class="text-uppercase small fw-semibold text-muted mb-1">
                            Resumen del cargue
                        </div>
                        <h3 class="h5 fw-bold mb-2">Importación de registros</h3>
                        <p class="text-muted mb-0">
                            El sistema asociará cada medición importada con la estación seleccionada,
                            el origen de dato tipo CSV y las variables físicas configuradas en EcoData.
                        </p>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <div class="small text-muted fw-semibold mb-2">Datos asociados al cargue</div>

                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between gap-3">
                                <span class="text-muted">Estación</span>
                                <span class="fw-semibold text-end">Seleccionada por el usuario</span>
                            </div>

                            <div class="d-flex justify-content-between gap-3">
                                <span class="text-muted">Origen</span>
                                <span class="fw-semibold text-end">Cargue CSV</span>
                            </div>

                            <div class="d-flex justify-content-between gap-3">
                                <span class="text-muted">Destino</span>
                                <span class="fw-semibold text-end">Registros físicos</span>
                            </div>

                            <div class="d-flex justify-content-between gap-3">
                                <span class="text-muted">Estado</span>
                                <span class="fw-semibold text-end">Listo para importar</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <div class="small text-muted fw-semibold mb-2">Validaciones del archivo</div>

                        <ul class="text-muted small mb-0 ps-3">
                            <li>Verifica la fecha y hora de cada medición.</li>
                            <li>Relaciona las columnas con variables físicas configuradas.</li>
                            <li>Asocia los registros a la estación meteorológica seleccionada.</li>
                            <li>Marca los datos importados con origen CSV.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Formato esperado del CSV</h2>
            <p class="text-muted mb-0">
                El archivo debe tener una columna de fecha y columnas para cada variable meteorológica
                capturada por la estación.
            </p>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Columna</th>
                        <th>Descripción</th>
                        <th>Ejemplo</th>
                        <th>Obligatoria</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-semibold">recorded_at</td>
                        <td>Fecha y hora de la medición.</td>
                        <td>2026-05-14 08:00:00</td>
                        <td><span class="badge rounded-pill text-bg-success">Sí</span></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">temperature</td>
                        <td>Temperatura registrada por la estación.</td>
                        <td>28.4</td>
                        <td><span class="badge rounded-pill text-bg-secondary">Según sensor</span></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">humidity</td>
                        <td>Humedad relativa del aire.</td>
                        <td>74</td>
                        <td><span class="badge rounded-pill text-bg-secondary">Según sensor</span></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">pressure</td>
                        <td>Presión atmosférica.</td>
                        <td>1012.5</td>
                        <td><span class="badge rounded-pill text-bg-secondary">Según sensor</span></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">rainfall</td>
                        <td>Precipitación registrada.</td>
                        <td>0</td>
                        <td><span class="badge rounded-pill text-bg-secondary">Según sensor</span></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">wind_speed</td>
                        <td>Velocidad del viento.</td>
                        <td>3.2</td>
                        <td><span class="badge rounded-pill text-bg-secondary">Según sensor</span></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">wind_direction</td>
                        <td>Dirección del viento.</td>
                        <td>NE</td>
                        <td><span class="badge rounded-pill text-bg-secondary">Según sensor</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card bg-white p-4">
        <div class="mb-4">
            <h2 class="h5 fw-bold mb-1">Ejemplo de archivo</h2>
            <p class="text-muted mb-0">
                Usa esta estructura como referencia para preparar los datos descargados desde la estación meteorológica.
            </p>
        </div>

        <div class="border rounded-4 p-4 bg-light">
            <pre class="mb-0 small"><code>recorded_at,temperature,humidity,pressure,rainfall,wind_speed,wind_direction
2026-05-14 08:00:00,28.4,74,1012.5,0,3.2,NE
2026-05-14 09:00:00,29.1,70,1012.2,0,3.5,NE
2026-05-14 10:00:00,30.0,68,1011.9,0,4.1,E</code></pre>
        </div>
    </section>
@endsection