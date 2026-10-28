{{-- Correo de confirmación. Estilos en línea: los clientes de correo ignoran las hojas de estilo. --}}
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:24px 12px;background:#f2f4f3;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#1c2521;">
    @php
        $logoSrc = $logo && $negocio->logo_path && is_file(public_path($negocio->logo_path)) ? $message->embed(public_path($negocio->logo_path)) : null;
    @endphp
    @include('emails._cuerpo', ['logoSrc' => $logoSrc])
</body>
</html>
