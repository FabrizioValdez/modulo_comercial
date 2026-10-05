<?php

namespace App\Http\Controllers\Ataque;

use App\Http\Controllers\Controller;
use App\Models\Ataque\AtaqueDecisor;
use App\Models\Ataque\AtaqueInvestigacion;
use App\Models\Ataque\AtaqueProyecto;
use App\Services\Ataque\PromptInvestigacion;
use Illuminate\Http\Request;

/**
 * Investigación sin API: el CRM arma el prompt, el usuario lo copia,
 * lo usa en su herramienta de IA y pega la respuesta aquí.
 */
class InvestigacionController extends Controller
{
    public function create(Request $request, AtaqueProyecto $proyecto, PromptInvestigacion $builder)
    {
        $proyecto->load('decisores');

        $decisorIds = array_map('intval', (array) $request->query('decisores', []));
        $fuentes = (array) $request->query('fuentes', config('ataque.fuentes_default'));
        $dias = (int) $request->query('dias', 90);
        if (!in_array($dias, [30, 90, 180], true)) {
            $dias = 90;
        }

        $prompt = $builder->generar($proyecto, $decisorIds, $fuentes, $dias);
        $historial = $proyecto->investigaciones()->limit(10)->get();

        return view('ataque.investigar', compact('proyecto', 'prompt', 'decisorIds', 'fuentes', 'dias', 'historial'));
    }

    public function store(Request $request, AtaqueProyecto $proyecto, PromptInvestigacion $builder)
    {
        $datos = $request->validate([
            'prompt' => 'required|string',
            'respuesta' => 'required|string',
            'herramienta' => 'nullable|string|max:80',
            'resumen_actual' => 'nullable|string|max:2000',
            'parametros' => 'nullable|string',
        ]);

        $json = $builder->leerRespuesta($datos['respuesta']);

        $inv = AtaqueInvestigacion::create([
            'ataque_proyecto_id' => $proyecto->id,
            'user_id' => optional($request->user())->id,
            'prompt' => $datos['prompt'],
            'respuesta' => $datos['respuesta'],
            'resultado' => $json,
            'herramienta' => $datos['herramienta'] ?? null,
            'parametros' => json_decode($datos['parametros'] ?? 'null', true),
        ]);

        // Resumen "lo actual" de la ficha: el que escribe el usuario o el del JSON.
        $resumen = trim((string) ($datos['resumen_actual'] ?? ''));
        if ($resumen === '' && $json && !empty($json['resumen_actual'])) {
            $resumen = (string) $json['resumen_actual'];
        }
        if ($resumen !== '') {
            $proyecto->update(['resumen_ia' => $resumen, 'resumen_ia_at' => now()]);
        }

        $nuevos = $json ? $this->agregarPersonasSugeridas($proyecto, $json) : 0;

        $msg = $json ? 'Informe guardado.' : 'Respuesta guardada como texto (no era JSON).';
        if ($nuevos) {
            $msg .= " $nuevos persona(s) nueva(s) sugeridas: revísalas en Decisores.";
        }

        return redirect()->route('ataque.investigacion.show', $inv)->with('ok', $msg);
    }

    public function show(AtaqueInvestigacion $investigacion)
    {
        $investigacion->load('proyecto');

        return view('ataque.informe', ['inv' => $investigacion, 'proyecto' => $investigacion->proyecto, 'r' => $investigacion->resultado ?: []]);
    }

    /** Personas que la IA encontró y no están en la ficha: se agregan como sugeridas. */
    protected function agregarPersonasSugeridas(AtaqueProyecto $proyecto, array $json)
    {
        $existentes = $proyecto->decisores()->pluck('nombre')->filter()->map(function ($n) {
            return mb_strtolower(trim($n));
        })->all();

        $personas = array_merge(
            isset($json['personas']) && is_array($json['personas']) ? $json['personas'] : [],
            isset($json['mapa_decision']) && is_array($json['mapa_decision']) ? $json['mapa_decision'] : []
        );

        $n = 0;
        foreach ($personas as $p) {
            $nombre = trim((string) ($p['nombre'] ?? ''));
            if ($nombre === '' || mb_strtolower($nombre) === 'sin dato' || in_array(mb_strtolower($nombre), $existentes, true)) {
                // Si ya existe, guarda su perfil IA.
                if ($nombre !== '' && isset($p['trayectoria'])) {
                    $proyecto->decisores()->whereRaw('lower(nombre) = ?', [mb_strtolower($nombre)])->update(['perfil_ia' => json_encode($p)]);
                }
                continue;
            }
            $linkedin = (string) ($p['linkedin'] ?? '');
            AtaqueDecisor::create([
                'ataque_proyecto_id' => $proyecto->id,
                'nombre' => $nombre,
                'cargo' => $p['cargo'] ?? null,
                'empresa' => $p['empresa'] ?? $proyecto->empresa,
                'rol_compra' => 'contacto',
                'linkedin_url' => filter_var($linkedin, FILTER_VALIDATE_URL) ? $linkedin : null,
                'perfil_ia' => isset($p['trayectoria']) ? $p : null,
                'sugerido_ia' => true,
            ]);
            $existentes[] = mb_strtolower($nombre);
            $n++;
        }

        return $n;
    }
}
