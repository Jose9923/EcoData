@component('mail::message')
@include('emails.partials.logo')

# Nueva guía de laboratorio disponible

Hola, **{{ $user->name }}**.

Se ha publicado una nueva guía de laboratorio en **EcoData**.

@component('mail::panel')
**Título:** {{ $guide->title }}

@if($guide->description)
**Descripción:**  
{{ $guide->description }}
@endif

@if($guide->grade)
**Grado:** {{ $guide->grade->label ?: $guide->grade->name }}
@endif

@if($guide->course)
**Curso:** {{ $guide->course->label ?: $guide->course->name }}
@endif

@if($guide->published_at)
**Fecha de publicación:** {{ $guide->published_at->format('d/m/Y') }}
@endif
@endcomponent

Puedes consultarla desde tu panel de estudiante.

@component('mail::button', ['url' => $url])
Ver mis guías
@endcomponent

Gracias,  
**Equipo EcoData**
@endcomponent