@php
    $logoPath = public_path('img/logo-login.jpeg');
    $logoSrc = file_exists($logoPath)
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp

@if($logoSrc)
    <div style="text-align: center; margin-bottom: 28px;">
        <img src="{{ $logoSrc }}"
             alt="EcoData"
             width="180"
             style="display: inline-block; width: 180px; max-width: 180px; height: auto; border-radius: 14px;">
    </div>
@endif