<?php

namespace App\Http\Controllers\Ataque;

use App\Http\Controllers\Controller;
use App\Models\Ataque\AtaqueDecisor;
use App\Models\Ataque\AtaqueProyecto;
use App\Services\Ataque\NoticiasService;

/**
 * App móvil instalable (PWA). Guarda los datos en el teléfono para usarlos sin
 * conexión y, con conexión, trae noticias y abre LinkedIn / Google.
 */
class MovilController extends Controller
{
    public function index()
    {
        return view('ataque.movil');
    }

    /** Todo lo que la app guarda en el teléfono. Sin facturación: no viaja al móvil. */
    public function datos(NoticiasService $noticias)
    {
        $proyectos = AtaqueProyecto::with(['decisores', 'actividades'])->get()->map(function (AtaqueProyecto $p) use ($noticias) {
            $empresa = $noticias->nombreCorto($p->empresa);

            return [
                'id' => $p->id,
                'empresa' => $p->empresa,
                'proyecto' => $p->proyecto,
                'rubro' => $p->rubro,
                'prioridad' => $p->prioridad,
                'prioridad_label' => $p->prioridad_label,
                'viabilidad' => $p->viabilidad,
                'ejecucion' => $p->ejecucion,
                'inversion' => $p->inversion,
                'contratista' => $p->subcontratista,
                'materiales' => $p->materiales_lista,
                'proximo_paso' => $p->proximo_paso,
                'resumen' => $p->resumen_ia,
                'datos_empresa' => $p->datos_empresa,
                'datos_fuente' => $p->datos_fuente,
                'alerta' => $p->alerta,
                'actividades' => $p->actividades->take(5)->map(function ($a) {
                    return ['fecha' => optional($a->fecha ?: $a->created_at)->format('d/m/Y'), 'texto' => $a->descripcion];
                })->values(),
                'enlaces' => [
                    'linkedin' => 'https://www.linkedin.com/search/results/companies/?keywords=' . rawurlencode($empresa),
                    'noticias' => 'https://news.google.com/search?q=' . rawurlencode('"' . $empresa . '"') . '&hl=es-419&gl=PE&ceid=PE:es-419',
                    'google' => 'https://www.google.com/search?q=' . rawurlencode($empresa . ' ' . $p->proyecto),
                ],
                'personas' => $p->decisores->map(function (AtaqueDecisor $d) use ($empresa) {
                    return [
                        'id' => $d->id,
                        'nombre' => $d->nombre,
                        'cargo' => $d->cargo,
                        'universidad' => $d->universidad,
                        'rol' => $d->rol_compra,
                        'rol_label' => $d->rol_label,
                        'nota' => $d->notas,
                        'sugerido' => $d->sugerido_ia,
                        'iniciales' => $d->getIniciales(),
                        'foto' => $d->foto_url,
                        'linkedin' => $d->linkedin_enlace,
                        'tiene_perfil' => (bool) $d->linkedin_url,
                        'google' => $d->nombre ? 'https://www.google.com/search?q=' . rawurlencode('"' . $d->nombre . '" ' . $empresa) : null,
                    ];
                })->values(),
            ];
        })->sortBy(function ($p) {
            $orden = ['inmediato' => 0, 'mediano' => 1, 'seguimiento' => 2, 'stand_by' => 3, 'identificado' => 4];

            return ($orden[$p['prioridad']] ?? 5) . $p['empresa'];
        })->values();

        return response()->json([
            'generado' => now()->toIso8601String(),
            'prioridades' => config('ataque.prioridades'),
            'proyectos' => $proyectos,
        ]);
    }

    public function noticias(AtaqueProyecto $proyecto, NoticiasService $noticias)
    {
        return response()->json($noticias->deProyecto($proyecto));
    }

    public function noticiasPersona(AtaqueDecisor $decisor, NoticiasService $noticias)
    {
        if (!$decisor->nombre) {
            return response()->json(['items' => []]);
        }

        return response()->json($noticias->dePersona($decisor->nombre, optional($decisor->proyecto)->empresa));
    }

    public function manifest()
    {
        return response()->json([
            'name' => 'Plan de Ataque Comercial',
            'short_name' => 'Plan de Ataque',
            'start_url' => route('ataque.movil'),
            'scope' => url(config('ataque.prefix')) . '/',
            'display' => 'standalone',
            'background_color' => '#F2F2EF',
            'theme_color' => '#17191C',
            'lang' => 'es',
            'icons' => [
                ['src' => asset('img/ataque-icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => asset('img/ataque-icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    /** El service worker se sirve bajo /ataque para poder controlar todo el módulo. */
    public function serviceWorker()
    {
        return response()
            ->view('ataque.sw-movil', ['version' => config('ataque.movil_version', '1')])
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache');
    }
}
