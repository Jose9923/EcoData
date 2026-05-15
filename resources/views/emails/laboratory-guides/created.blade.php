@component('mail::message')
# Nueva guía de laboratorio disponible

Hola, **{{ $user->name }}**.

Se ha publicado una nueva guía de laboratorio en **EcoData**.

@component('mail::panel')
**Título:** {{ $guide->title }}

@if($guide->description)
**Descripción:**  
{{ $guide->description }}
@endif
@endcomponent

Puedes consultarla desde tu panel de estudiante.

@component('mail::button', ['url' => $url])
Ver mis guías
@endcomponent

Gracias,  
**Equipo EcoData**
@endcomponent
