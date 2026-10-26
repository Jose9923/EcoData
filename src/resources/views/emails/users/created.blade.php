@component('mail::message')
@include('emails.partials.logo')

# Bienvenido a EcoData

Hola, **{{ $user->name }}**.

Se ha creado una cuenta para ti en la plataforma **EcoData**.

@component('mail::panel')
**Correo de acceso:** {{ $user->email }}

**Rol asignado:** {{ $roleName ?? 'Usuario' }}

@endcomponent

Establece tu propia contraseña para completar el acceso. Este enlace es individual, de un solo uso y vence en {{ $expiresInMinutes }} minutos.

@component('mail::button', ['url' => $activationUrl])
Establecer mi contraseña
@endcomponent

Si el enlace venció, solicita uno nuevo desde «Olvidé mi contraseña» en la pantalla de ingreso.

Gracias,  
**Equipo EcoData**
@endcomponent