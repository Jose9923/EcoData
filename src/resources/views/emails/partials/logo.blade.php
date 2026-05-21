@php
    $logoUrl = config('app.url') . '/img/logo-login.jpeg';
@endphp

<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding: 24px 0;">
            <img
                src="{{ $logoUrl }}"
                alt="EcoData"
                width="160"
                style="display: block; max-width: 160px; height: auto; border: 0;"
            >
        </td>
    </tr>
</table>