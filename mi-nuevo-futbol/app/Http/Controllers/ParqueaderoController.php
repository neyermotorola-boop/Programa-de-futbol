<?php

namespace App\Http\Controllers;

use App\Models\Mensualidad;
use App\Models\ParqueaderoIngreso;
use App\Models\TipoVehiculo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParqueaderoController extends Controller
{
    /**
     * El parqueadero con el que se trabaja: el del dueño, o el de su dueño si quien entró es un operador.
     * Solo los usuarios de parqueadero entran a este panel.
     */
    private function negocio()
    {
        $usuario = Auth::user();
        abort_unless($usuario && $usuario->esParqueadero(), 403);

        return $usuario->esOperador() ? $usuario->dueno : $usuario;
    }

    /** Tarifas, anulaciones y demás ajustes son solo del dueño: un operador solo registra ingresos y salidas. */
    private function soloDueno(): void
    {
        abort_if(Auth::user()->esOperador(), 403, 'Esta acción es solo para el dueño del parqueadero.');
    }

    private function propio($modelo): void
    {
        abort_unless($modelo->user_id === $this->negocio()->id, 404);
    }

    /**
     * Panel: carros dentro ahora, formulario de ingreso, y resumen/historial del día.
     */
    public function index(Request $request)
    {
        $negocio = $this->negocio();

        $fecha = $request->query('fecha');
        $fecha = $fecha && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : now(ParqueaderoIngreso::ZONA)->format('Y-m-d');

        // El día se cuenta en hora local, pero se guarda en UTC
        $desde = Carbon::parse($fecha, ParqueaderoIngreso::ZONA)->startOfDay()->utc();
        $hasta = $desde->copy()->addDay();

        $tipos = $negocio->tiposVehiculo()->get();
        $dentro = $negocio->parqueaderoIngresos()->with('tipo')->dentro()->orderBy('entrada')->get();

        // El operador no ve el historial ni lo recaudado: solo el dueño
        $esOperador = Auth::user()->esOperador();
        $salidas = $esOperador ? collect() : $negocio->parqueaderoIngresos()->with('cobrador')
            ->whereNotNull('salida')
            ->where('salida', '>=', $desde)->where('salida', '<', $hasta)
            ->orderByDesc('salida')
            ->get();

        return view('parqueadero.index', [
            'negocio' => $negocio,
            'esOperador' => $esOperador,
            'operadores' => $esOperador ? collect() : $negocio->operadores()->get(),
            'mensualidades' => $esOperador ? collect() : $negocio->mensualidades()->with('pagos')->get(),
            'tipos' => $tipos,
            'dentro' => $dentro,
            'salidas' => $salidas,
            'fecha' => $fecha,
            'esHoy' => $fecha === now(ParqueaderoIngreso::ZONA)->format('Y-m-d'),
            'recaudado' => $salidas->sum('valor_cobrado'),
        ]);
    }

    /**
     * Registra la entrada de un vehículo. La hora de ingreso es la del momento en que se anota.
     */
    public function ingresar(Request $request)
    {
        $negocio = $this->negocio();

        $data = $request->validate([
            'placa' => ['required', 'string', 'max:12'],
            'tipo_id' => ['required', 'integer'],
        ]);

        $tipo = $negocio->tiposVehiculo()->find($data['tipo_id']);
        if (! $tipo) {
            return back()->withErrors(['tipo_id' => 'Elige un tipo de vehículo válido.'])->withInput();
        }

        // Una cicla puede no tener placa: se anota un número o nombre para reconocerla
        $placa = ParqueaderoIngreso::normalizarPlaca($data['placa']);
        if ($placa === '') {
            return back()->withErrors(['placa' => 'Escribe la placa (o un código para reconocer el vehículo).'])->withInput();
        }

        if ($negocio->parqueaderoIngresos()->dentro()->where('placa', $placa)->exists()) {
            return back()->withErrors(['placa' => "El vehículo {$placa} ya está dentro del parqueadero."])->withInput();
        }

        // ¿Tiene mensualidad? Vigente: entra y sale sin cobro. Vencida: se cobra la tarifa normal y se avisa.
        $mensualidad = $negocio->mensualidades()->where('placa', $placa)->where('activa', true)->first();
        $vigente = $mensualidad?->vigente() ? $mensualidad : null;

        $negocio->parqueaderoIngresos()->create([
            'placa' => $placa,
            'tipo_id' => $tipo->id,
            'mensualidad_id' => $vigente?->id,
            'vehiculo' => $tipo->nombre,
            'registrado_por' => Auth::id(),
            'entrada' => now(),
        ]);

        $respuesta = redirect()->route('parqueadero.index')->with('exito', "Ingreso registrado: {$tipo->nombre} {$placa}.");

        if ($vigente) {
            $aviso = "Mensualidad vigente de {$vigente->cliente} hasta el " . $vigente->vence->format('d/m/Y') . ': no se cobra la salida.';
            if ($vigente->diasRestantes() <= Mensualidad::AVISO_DIAS) {
                $aviso .= ' ' . $vigente->textoDias() . ': recuérdale que debe renovarla.';
            }
            $respuesta->with('aviso', $aviso);
        } elseif ($mensualidad) {
            $respuesta->with('aviso', "La mensualidad de {$mensualidad->cliente} venció el " . $mensualidad->vence->format('d/m/Y') . '. Se cobrará la tarifa normal hasta que la renueve.');
        }

        return $respuesta;
    }

    /**
     * Registra la salida: calcula el tiempo y el valor con la tarifa de su tipo y los deja guardados.
     */
    public function salir(ParqueaderoIngreso $ingreso)
    {
        $this->negocio();
        $this->propio($ingreso);

        if ($ingreso->salida) {
            return redirect()->route('parqueadero.index')->withErrors(['placa' => 'Esa salida ya estaba registrada.']);
        }

        $tipo = $ingreso->tipo;
        $salida = now();
        $minutos = $ingreso->minutosTranscurridos($salida);
        // Con mensualidad vigente al entrar no se cobra
        $conMensualidad = $ingreso->mensualidad_id !== null;
        $valor = $conMensualidad ? 0 : ParqueaderoIngreso::calcularValor($minutos, $tipo->tarifa_tipo, (float) $tipo->tarifa_valor);

        $ingreso->update([
            'salida' => $salida,
            'minutos' => $minutos,
            'tarifa_tipo' => $conMensualidad ? 'mensualidad' : $tipo->tarifa_tipo,
            'tarifa_valor' => $conMensualidad ? 0 : $tipo->tarifa_valor,
            'valor_cobrado' => $valor,
            'cobrado_por' => Auth::id(),
        ]);

        return redirect()->route('parqueadero.index')->with('recibo', [
            'placa' => $ingreso->placa,
            'vehiculo' => $ingreso->vehiculo,
            'atendio' => Auth::user()->name,
            'entrada' => $ingreso->entradaLocal()->format('d/m/Y h:i a'),
            'salida' => $ingreso->salidaLocal()->format('d/m/Y h:i a'),
            'duracion' => ParqueaderoIngreso::textoDuracion($minutos),
            'tarifa' => $conMensualidad ? 'Mensualidad vigente' : $tipo->textoTarifa(),
            'valor' => $valor,
        ]);
    }

    /** Borra un ingreso anotado por error (solo mientras el vehículo sigue dentro). */
    public function eliminar(ParqueaderoIngreso $ingreso)
    {
        $this->soloDueno();
        $this->negocio();
        $this->propio($ingreso);
        abort_if($ingreso->salida, 403, 'Solo se puede anular un ingreso que todavía no tiene salida.');

        $ingreso->delete();

        return redirect()->route('parqueadero.index')->with('exito', "Ingreso de {$ingreso->placa} anulado.");
    }

    private function validarTipo(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:30'],
            'tarifa_tipo' => ['required', 'in:hora,minuto'],
            'tarifa_valor' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);
    }

    /** Agrega un tipo de vehículo (carro, moto, cicla, camioneta...) con su tarifa. */
    public function crearTipo(Request $request)
    {
        $this->soloDueno();
        $negocio = $this->negocio();
        $data = $this->validarTipo($request);

        if ($negocio->tiposVehiculo()->whereRaw('lower(nombre) = ?', [mb_strtolower($data['nombre'])])->exists()) {
            return back()->withErrors(['nombre' => "Ya tienes un tipo llamado {$data['nombre']}."])->withInput();
        }

        $negocio->tiposVehiculo()->create($data + ['orden' => ($negocio->tiposVehiculo()->max('orden') ?? 0) + 1]);

        return redirect()->route('parqueadero.index')->with('exito', "Tipo {$data['nombre']} agregado.");
    }

    /** Cambia el nombre o la tarifa de un tipo. Se aplica a las próximas salidas. */
    public function actualizarTipo(Request $request, TipoVehiculo $tipo)
    {
        $this->soloDueno();
        $this->negocio();
        $this->propio($tipo);
        $data = $this->validarTipo($request);

        $repetido = $tipo->usuario->tiposVehiculo()->where('id', '!=', $tipo->id)->whereRaw('lower(nombre) = ?', [mb_strtolower($data['nombre'])])->exists();
        if ($repetido) {
            return back()->withErrors(['nombre' => "Ya tienes un tipo llamado {$data['nombre']}."])->withInput();
        }

        $tipo->update($data);
        // Los que están dentro ahora conservan el nombre al día
        $tipo->ingresos()->dentro()->update(['vehiculo' => $data['nombre']]);

        return redirect()->route('parqueadero.index')->with('exito', "Tipo {$tipo->nombre} actualizado. La tarifa se aplica a las próximas salidas.");
    }

    public function eliminarTipo(TipoVehiculo $tipo)
    {
        $this->soloDueno();
        $negocio = $this->negocio();
        $this->propio($tipo);

        if ($tipo->ingresos()->dentro()->exists()) {
            return back()->withErrors(['nombre' => "No puedes eliminar {$tipo->nombre} mientras haya vehículos de ese tipo dentro."]);
        }
        if ($negocio->tiposVehiculo()->count() <= 1) {
            return back()->withErrors(['nombre' => 'Debe quedar al menos un tipo de vehículo.']);
        }

        $tipo->delete();

        return redirect()->route('parqueadero.index')->with('exito', "Tipo {$tipo->nombre} eliminado. Su historial se conserva.");
    }
}
