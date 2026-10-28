<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center">
    <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;">
        <tr><td style="background:{{ $paleta['primario'] }};color:{{ $paleta['texto_primario'] }};padding:20px 24px;font-size:18px;font-weight:bold;">{{ $dueno->name }}</td></tr>
        <tr><td style="padding:24px;">
            <h1 style="margin:0 0 8px;font-size:20px;">Mensualidades que requieren atención</h1>
            <p style="margin:0 0 18px;color:#4b5563;font-size:14px;line-height:1.5;">Estos vehículos están por cumplir el mes de su mensualidad o ya lo cumplieron. Recuérdales renovar para que sigan entrando sin cobro.</p>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;border-collapse:collapse;">
                @foreach ($mensualidades as $m)
                    <tr>
                        <td style="padding:10px 0;border-top:1px solid #e5e7eb;"><b style="letter-spacing:1px;">{{ $m->placa }}</b><br><span style="color:#6b7280;">{{ $m->cliente }}@if ($m->telefono) · {{ $m->telefono }}@endif</span></td>
                        <td align="right" style="padding:10px 0;border-top:1px solid #e5e7eb;"><b>{{ $m->vence->format('d/m/Y') }}</b><br><span style="color:{{ $m->diasRestantes() < 0 ? '#b91c1c' : '#b45309' }};">{{ $m->textoDias() }}</span></td>
                    </tr>
                @endforeach
            </table>
            <p style="margin:22px 0 0;"><a href="{{ $url }}" style="display:inline-block;background:{{ $paleta['acento'] }};color:{{ $paleta['texto_acento'] }};text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:10px;">Ver mensualidades</a></p>
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
