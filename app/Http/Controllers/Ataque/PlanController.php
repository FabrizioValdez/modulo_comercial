<?php

namespace App\Http\Controllers\Ataque;

use App\Http\Controllers\Controller;
use App\Models\Ataque\AtaqueActividad;
use App\Models\Ataque\AtaqueProyecto;
use App\Services\Ataque\ClienteMatcher;
use App\Services\Ataque\ErpFacturacion;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $prioridad = $request->query('prioridad');
        $q = trim((string) $request->query('q'));

        $base = AtaqueProyecto::query();
        $conteos = (clone $base)->selectRaw('prioridad, count(*) as total')->groupBy('prioridad')->pluck('total', 'prioridad');

        $proyectos = $base->with('decisores')
            ->when($prioridad, function ($w) use ($prioridad) {
                $w->where('prioridad', $prioridad);
            })
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($s) use ($q) {
                    $s->where('empresa', 'ilike', "%$q%")
                        ->orWhere('proyecto', 'ilike', "%$q%")
                        ->orWhereHas('decisores', function ($d) use ($q) {
                            $d->where('nombre', 'ilike', "%$q%");
                        });
                });
            })
            ->orderByRaw("CASE prioridad WHEN 'inmediato' THEN 0 WHEN 'mediano' THEN 1 WHEN 'seguimiento' THEN 2 WHEN 'stand_by' THEN 3 WHEN 'identificado' THEN 4 ELSE 5 END")
            ->orderBy('empresa')
            ->get();

        $sinDecisor = AtaqueProyecto::whereDoesntHave('decisores', function ($d) {
            $d->whereNotNull('nombre');
        })->count();

        return view('ataque.index', compact('proyectos', 'conteos', 'prioridad', 'q', 'sinDecisor'));
    }

    /** Ficha del proyecto: solo datos actuales. */
    public function show(AtaqueProyecto $proyecto, ClienteMatcher $matcher, ErpFacturacion $erp)
    {
        $proyecto->load(['decisores', 'ultimaInvestigacion']);
        $actividades = $proyecto->actividades()->limit(8)->get();

        $cliente = $matcher->cliente($proyecto->client_id);
        $facturacion = $cliente ? $erp->resumenActual($cliente->number_document) : null;
        $cotizaciones = $cliente ? $erp->cotizacionesActuales($cliente->id) : null;
        $candidatos = $cliente ? collect() : $matcher->candidatos($proyecto->empresa);

        $contratista = $matcher->cliente($proyecto->contratista_client_id);
        $contratistaFact = $contratista ? $erp->resumenActual($contratista->number_document) : null;

        return view('ataque.show', compact(
            'proyecto', 'actividades', 'cliente', 'facturacion', 'cotizaciones', 'candidatos', 'contratista', 'contratistaFact'
        ));
    }

    /** Relaciona (o quita) el cliente o el contratista del CRM. */
    public function relacionar(Request $request, AtaqueProyecto $proyecto, ClienteMatcher $matcher)
    {
        $datos = $request->validate([
            'tipo' => 'required|in:cliente,contratista',
            'client_id' => 'nullable|integer',
            'ruc' => 'nullable|string|max:20',
        ]);

        $id = $datos['client_id'] ?? null;
        if (!$id && !empty($datos['ruc'])) {
            $c = $matcher->porRuc($datos['ruc']);
            if (!$c) {
                return back()->withErrors(['ruc' => 'No hay ningún cliente con RUC ' . $datos['ruc'] . ' en el CRM.']);
            }
            $id = $c->id;
        }

        $campo = $datos['tipo'] === 'cliente' ? 'client_id' : 'contratista_client_id';
        $proyecto->update([$campo => $id]);

        return back()->with('ok', $id ? 'Relación guardada.' : 'Relación quitada.');
    }

    public function actividad(Request $request, AtaqueProyecto $proyecto)
    {
        $datos = $request->validate([
            'descripcion' => 'required|string|max:5000',
            'fecha' => 'nullable|date',
            'tipo' => 'nullable|string|max:40',
            'proximo_paso' => 'nullable|string|max:2000',
        ]);

        AtaqueActividad::create([
            'ataque_proyecto_id' => $proyecto->id,
            'user_id' => optional($request->user())->id,
            'fecha' => $datos['fecha'] ?? now()->toDateString(),
            'tipo' => $datos['tipo'] ?? 'nota',
            'descripcion' => $datos['descripcion'],
        ]);

        if (!empty($datos['proximo_paso'])) {
            $proyecto->update(['proximo_paso' => $datos['proximo_paso']]);
        }

        return back()->with('ok', 'Actividad registrada.');
    }
}
