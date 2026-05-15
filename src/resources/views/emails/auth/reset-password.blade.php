@component('mail::message')
@include('emails.partials.logo')

# Restablecimiento de contraseña

Hola, **{{ $user->name }}**.

Recibimos una solicitud para restablecer la contraseña de tu cuenta en **EcoData**.

@component('mail::panel')
Si realizaste esta solicitud, haz clic en el botón inferior para crear una nueva contraseña.
@endcomponent

@component('mail::button', ['url' => $url])
Restablecer contraseña
@endcomponent

Este enlace es temporal por seguridad. Si no solicitaste el cambio de contraseña, puedes ignorar este mensaje.

Gracias,  
**Equipo EcoData**
@endcomponent
