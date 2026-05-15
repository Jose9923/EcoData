@component('mail::message')
@include('emails.partials.logo')

# Nueva actividad de Diario de Campo

Hola, **{{ $user->name }}**.

Se ha publicado una nueva actividad de **Diario de Campo** en EcoData.

@component('mail::panel')
**Actividad:** {{ $activity->title }}

@if($activity->description)
**Descripción:**  
{{ $activity->description }}
@endif

@if($activity->entry_type)
**Tipo de actividad:** {{ ucfirst(str_replace('_', ' ', $activity->entry_type)) }}
@endif

@if($activity->grade)
**Grado:** {{ $activity->grade->label ?: $activity->grade->name }}
@endif

@if($activity->course)
**Curso:** {{ $activity->course->label ?: $activity->course->name }}
@endif

@if($activity->weatherStation)
**Estación meteorológica:** {{ $activity->weatherStation->name }}
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