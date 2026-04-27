<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpeg" href="{{ asset('img/favicon.ico') }}">
    <title>EcoData</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-3">EcoData</h1>
        <p class="text-secondary">Bienvenido al sistema.</p>
        <a href="{{ route('login') }}" class="btn btn-primary">Iniciar sesión</a>
    </div>
</body>
</html>