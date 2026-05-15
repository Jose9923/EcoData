@component('mail::message')
# Tu Diario de Campo fue revisado

Hola, **{{ $user->name }}**.

Tu entrega de **Diario de Campo** ha sido revisada.

@component('mail::panel')
**Actividad:** {{ $activity?->title ?? 'Diario de Campo' }}

@if(! is_null($submission->score))
**Calificación:** {{ $submission->score }}
@endif

@if($submission->teacher_feedback)
**Retroalimentación:**  
{{ $submission->teacher_feedback }}
@endif
@endcomponent

Puedes ingresar a EcoData para consultar el detalle de tu entrega.

@component('mail::button', ['url' => $url])
Ver mi Diario de Campo
@endcomponent

Gracias,  
**Equipo EcoData**
@endcomponent