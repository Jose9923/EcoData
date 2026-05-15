@component('mail::message')
# Bienvenido a EcoData

Hola, **{{ $user->name }}**.

Se ha creado una cuenta para ti en la plataforma **EcoData**.

@component('mail::panel')
**Correo de acceso:** {{ $user->email }}

**Rol asignado:** {{ $roleName ?? 'Usuario' }}

**Contraseña temporal:** {{ $temporaryPassword }}
@endcomponent

Por seguridad, te recomendamos cambiar tu contraseña después de iniciar sesión.

@component('mail::button', ['url' => $loginUrl])
Ingresar a EcoData
@endcomponent

Gracias,  
**Equipo EcoData**
@endcomponent
