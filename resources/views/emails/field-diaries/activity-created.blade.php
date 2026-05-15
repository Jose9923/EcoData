@component('mail::message')
# Nueva actividad de Diario de Campo

Hola, **{{ $user->name }}**.

Se ha publicado una nueva actividad de **Diario de Campo** en EcoData.

@component('mail::panel')
**Actividad:** {{ $activity->title }}

@if($activity->description)
**Descripción:**  
{{ $activity->description }}
@endif

@if($activity->starts_at)
**Fecha de inicio:** {{ $activity->starts_at->format('d/m/Y') }}
@endif

@if($activity->ends_at)
**Fecha límite:** {{ $activity->ends_at->format('d/m/Y') }}
@endif
@endcomponent

Ingresa a la plataforma para responder la actividad.

@component('mail::button', ['url' => $url])
Responder Diario de Campo
@endcomponent

Gracias,  
**Equipo EcoData**
@endcomponent
