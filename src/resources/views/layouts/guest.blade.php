<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('img/favicon.ico') }}">
    <title>{{ config('app.name', 'EcoData') }}</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="min-vh-100 position-relative overflow-hidden" style="background: linear-gradient(180deg, #0a1f17 0%, #10281f 28%, #163327 52%, #1d3b2e 72%, #274838 100%);">
        <div class="position-absolute top-0 start-0 translate-middle rounded-circle"
             style="width: 22rem; height: 22rem; background: rgba(16,185,129,.18); filter: blur(80px);"></div>
        <div class="position-absolute top-0 end-0 translate-middle rounded-circle"
             style="width: 24rem; height: 24rem; background: rgba(34,197,94,.12); filter: blur(90px);"></div>
        <div class="position-absolute bottom-0 start-50 translate-middle rounded-circle"
             style="width: 20rem; height: 20rem; background: rgba(13,148,136,.12); filter: blur(80px);"></div>

        <div class="container position-relative">
            <div class="row min-vh-100 align-items-center py-5 g-4">
                <div class="col-lg-7 d-none d-lg-block">
                    <div class="text-white pe-lg-5">
                        <p class="small fw-semibold text-uppercase mb-3" style="letter-spacing: .28em; color: #86efac;">
                            EcoData
                        </p>

                        <h1 class="display-4 fw-bold lh-sm mb-4">
                            Datos ambientales escolares con una interfaz clara y útil.
                        </h1>

                        <p class="fs-6 text-white-50 mb-4" style="max-width: 38rem;">
                            Gestiona colegios, usuarios, variables físicas, registros y reportes desde una plataforma
                            diseñada para integrar datos, territorio y educación ambiental.
                        </p>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="border rounded-4 p-4 h-100 shadow-sm"
                                     style="background: rgba(255,255,255,.10); border-color: rgba(255,255,255,.35) !important; backdrop-filter: blur(8px);">
                                    <p class="small fw-semibold text-uppercase mb-2" style="letter-spacing: .2em; color: #bbf7d0;">
                                        Monitoreo
                                    </p>
                                    <h3 class="h5 fw-bold text-white">Registros físicos dinámicos</h3>
                                    <p class="small text-white-50 mb-0">
                                        Captura datos de clima, agua, suelo y laboratorio sin depender de formularios rígidos.
                                    </p>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="border rounded-4 p-4 h-100 shadow-sm"
                                     style="background: rgba(255,255,255,.10); border-color: rgba(255,255,255,.35) !important; backdrop-filter: blur(8px);">
                                    <p class="small fw-semibold text-uppercase mb-2" style="letter-spacing: .2em; color: #bbf7d0;">
                                        Análisis
                                    </p>
                                    <h3 class="h5 fw-bold text-white">Consulta y reportes</h3>
                                    <p class="small text-white-50 mb-0">
                                        Filtra y analiza información ambiental escolar con una experiencia sobria y consistente.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</body>
</html>