{{-- Cuerpo del correo de confirmación; se usa en el correo real y en la vista previa de Configuración. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e3e8e5;color:#1c2521;">
    <tr>
        <td style="background:{{ $paleta['primario'] }};padding:24px 28px;color:{{ $paleta['texto_primario'] }};">
            <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                @if ($logoSrc ?? $logo)
                    <td style="padding-right:14px;"><img src="{{ $logoSrc ?? $logo }}" alt="" width="52" height="52" style="display:block;width:52px;height:52px;border-radius:12px;background:#ffffff;object-fit:contain;"></td>
                @endif
                <td>
                    <div style="font-size:19px;font-weight:700;line-height:1.2;">{{ $negocio->name }}</div>
                    @if ($negocio->eslogan)<div style="font-size:13px;opacity:.85;margin-top:2px;">{{ $negocio->eslogan }}</div>@endif
                </td>
            </tr></table>
        </td>
    </tr>
    <tr>
        <td style="padding:26px 28px 6px;">
            <div style="display:inline-block;background:{{ $paleta['acento'] }};color:{{ $paleta['texto_acento'] }};font-size:12px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:5px 12px;border-radius:999px;">Cita confirmada</div>
            <p data-prev="mensaje" style="font-size:16px;line-height:1.55;margin:16px 0 18px;">{{ $mensaje }}</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e3e8e5;border-radius:12px;">
                @foreach ($filas as $i => [$etiqueta, $valor])
                    <tr>
                        <td style="padding:11px 16px;font-size:13px;color:#5b6761;width:36%;{{ $i ? 'border-top:1px solid #e3e8e5;' : '' }}">{{ $etiqueta }}</td>
                        <td style="padding:11px 16px;font-size:15px;font-weight:600;{{ $i ? 'border-top:1px solid #e3e8e5;' : '' }}">{{ $valor }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 28px 26px;">
            <p data-prev="pie" style="font-size:14px;line-height:1.5;color:#5b6761;margin:0 0 12px;">{{ $pie }}</p>
            @if ($negocio->direccion || $negocio->telefono_contacto)
                <div style="font-size:13px;color:#5b6761;border-top:1px solid #e3e8e5;padding-top:12px;">
                    @if ($negocio->direccion){{ $negocio->direccion }}@endif
                    @if ($negocio->direccion && $negocio->telefono_contacto) · @endif
                    @if ($negocio->telefono_contacto){{ $negocio->telefono_contacto }}@endif
                </div>
            @endif
        </td>
    </tr>
</table>
