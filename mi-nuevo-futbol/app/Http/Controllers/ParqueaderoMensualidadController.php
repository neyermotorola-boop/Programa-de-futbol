<?php

namespace App\Http\Controllers;

use App\Models\Mensualidad;
use App\Models\ParqueaderoIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mensualidades del parqueadero: vehículos que pagan por mes y entran sin cobro mientras estén vigentes.
 * Solo el dueño las administra (maneja el dinero); el operador solo ve el aviso al registrar el ingreso.
 */
class ParqueaderoMensualidadController extends Controller
{
    private function dueno()
    {
        $usuario = Auth::user();
        abort_unless($usuario && $usuario->esParqueadero() && ! $usuario->esOperador(), 403);

        return $usuario;
    }

    private function propia(Mensualidad $mensualidad): void
    {
        abort_unless($mensualidad->user_id === $this->dueno()->id, 404);
    }

    private function volver(string $mensaje, string $clave = 'exito')
    {
        return redirect()->to(route('parqueadero.index') . '#mensualidades')->with($clave, $mensaje);
    }

    public function crear(Request $request)
    {
        $dueno = $this->dueno();

        $data = $request->validate([
            'placa' => ['required', 'string', 'max:12'],
            'tipo_id' => ['required', 'integer'],
            'cliente' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'inicio' => ['nullable', 'date'],
        ]);

        $tipo = $dueno->tiposVehiculo()->find($data['tipo_id']);
        if (! $tipo) {
            return back()->withErrors(['tipo_id' => 'Elige un tipo de vehículo válido.'])->withInput();
        }

        $placa = ParqueaderoIngreso::normalizarPlaca($data['placa']);
        if ($placa === '') {
            return back()->withErrors(['placa' => 'Escribe la placa del vehículo.'])->withInput();
        }
        if ($dueno->mensualidades()->where('placa', $placa)->where('activa', true)->exists()) {
            return back()->withErrors(['placa' => "El vehículo {$placa} ya tiene una mensualidad activa. Renuévala desde la lista."])->withInput();
        }

        $inicio = \Carbon\Carbon::parse($data['inicio'] ?? Mensualidad::hoy());

        $mensualidad = $dueno->mensualidades()->create([
            'tipo_id' => $tipo->id,
            'vehiculo' => $tipo->nombre,
            'placa' => $placa,
            'cliente' => $data['cliente'],
            'telefono' => $data['telefono'] ?? null,
            'valor' => $data['valor'],
            'inicio' => $inicio->toDateString(),
            'vence' => $inicio->toDateString(),
        ]);

        // El primer mes se registra como un pago: cubre desde el inicio hasta un mes después
        $pago = $mensualidad->pagos()->create([
            'user_id' => $dueno->id,
            'valor' => $data['valor'],
            'desde' => $inicio->toDateString(),
            'hasta' => $inicio->copy()->addMonthNoOverflow()->toDateString(),
            'cobrado_por' => Auth::id(),
        ]);
        $mensualidad->update(['vence' => $pago->hasta->toDateString()]);

        return $this->volver("Mensualidad de {$placa} creada. " . ($mensualidad->vigente()
            ? 'Vigente hasta el ' . $mensualidad->vence->format('d/m/Y') . '.'
            : 'Ese mes ya se cumplió el ' . $mensualidad->vence->format('d/m/Y') . ': renuévala para que vuelva a estar vigente.'));
    }

    /** Registra el pago de otro mes: extiende la vigencia. */
    public function renovar(Request $request, Mensualidad $mensualidad)
    {
        $this->propia($mensualidad);

        $data = $request->validate(['valor' => ['required', 'numeric', 'min:0', 'max:99999999']]);

        $mensualidad->renovar((float) $data['valor'], Auth::id());

        return $this->volver("Mensualidad de {$mensualidad->placa} renovada hasta el " . $mensualidad->vence->format('d/m/Y') . '.');
    }

    /** Da de baja (o reactiva) una mensualidad sin borrar su historial de pagos. */
    public function estado(Mensualidad $mensualidad)
    {
        $this->propia($mensualidad);

        if (! $mensualidad->activa && Auth::user()->mensualidades()->where('placa', $mensualidad->placa)->where('activa', true)->exists()) {
            return $this->volver("El vehículo {$mensualidad->placa} ya tiene otra mensualidad activa.", 'error_mensualidad');
        }

        $mensualidad->update(['activa' => ! $mensualidad->activa]);

        return $this->volver($mensualidad->activa
            ? "Mensualidad de {$mensualidad->placa} reactivada."
            : "Mensualidad de {$mensualidad->placa} dada de baja. Su historial de pagos se conserva.");
    }
}
