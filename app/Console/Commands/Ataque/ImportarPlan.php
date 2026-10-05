<?php

namespace App\Console\Commands\Ataque;

use App\Models\Ataque\AtaqueActividad;
use App\Models\Ataque\AtaqueDecisor;
use App\Models\Ataque\AtaqueProyecto;
use App\Models\Ataque\ObraPublica;
use App\Services\Ataque\ClienteMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * php artisan ataque:importar "storage/app/Plan_Estrategico_Ataque_Comercial.xlsx"
 *
 * Lee la hoja del plan ("REC_Plan de Ataque Comercial") y la de Boletines.
 * Se puede correr varias veces: actualiza por empresa + proyecto sin duplicar.
 */
class ImportarPlan extends Command
{
    protected $signature = 'ataque:importar {archivo : Ruta al .xlsx} {--hoja-plan=REC_Plan de Ataque Comercial} {--hoja-boletines=Boletines}';

    protected $description = 'Importa el Excel del Plan de Ataque Comercial al CRM';

    public function handle(ClienteMatcher $matcher)
    {
        $archivo = $this->argument('archivo');
        if (!is_file($archivo)) {
            $this->error('No encuentro el archivo: ' . $archivo);

            return 1;
        }

        $libro = IOFactory::load($archivo);

        $plan = $libro->getSheetByName($this->option('hoja-plan'));
        if ($plan) {
            DB::transaction(function () use ($plan, $matcher) {
                $this->importarPlan($plan->toArray(null, true, false, false), $matcher);
            });
        } else {
            $this->warn('No hay hoja "' . $this->option('hoja-plan') . '"');
        }

        $boletines = $libro->getSheetByName($this->option('hoja-boletines'));
        if ($boletines) {
            $this->importarBoletines($boletines->toArray(null, true, false, false));
        }

        return 0;
    }

    protected function importarPlan(array $filas, ClienteMatcher $matcher)
    {
        array_shift($filas); // encabezado
        $creados = 0;
        $actualizados = 0;

        foreach ($filas as $f) {
            $f = array_map(function ($v) {
                return is_string($v) ? trim($v) : $v;
            }, $f);

            // Filas separadoras ("REC 1 SEMANA DE SEPT") no tienen empresa.
            if (empty($f[1]) || empty($f[4] ?? null) && empty($f[3] ?? null)) {
                continue;
            }

            // Algunas filas no traen la columna Subcontratista y vienen corridas una posición.
            if (!$this->esViabilidad($f[6] ?? '') && $this->esViabilidad($f[5] ?? '')) {
                array_splice($f, 2, 0, [null]);
            }

            list($prioridad, $prioridadTexto) = $this->prioridad($f[0] ?? '');
            $linkedin = $f[11] ?? '';
            $accion = $f[12] ?? null;

            $datos = [
                'subcontratista' => $f[2] ?: null,
                'rubro' => $f[3] ?? null,
                'resena' => $f[5] ?? null,
                'prioridad' => $prioridad,
                'prioridad_texto' => $prioridadTexto,
                'viabilidad' => $f[6] ?? null,
                'ejecucion' => $f[7] ?? null,
                'inversion' => $this->inversion(($f[4] ?? '') . ' ' . ($f[5] ?? '')),
                'materiales' => $f[8] ?? null,
                'accion_comercial' => $accion,
                'busqueda_linkedin' => (stripos($linkedin, 'http') === 0 || $linkedin === $accion) ? null : ($linkedin ?: null),
            ];

            $proyecto = AtaqueProyecto::firstOrNew(['empresa' => $f[1], 'proyecto' => $f[4]]);
            $nuevo = !$proyecto->exists;
            $proyecto->fill($datos);
            if (!$proyecto->proximo_paso) {
                $proyecto->proximo_paso = $accion;
            }
            $proyecto->save();
            $nuevo ? $creados++ : $actualizados++;

            if ($nuevo) {
                $this->crearDecisores($proyecto, $f[9] ?? '', $f[10] ?? '', stripos($linkedin, 'http') === 0 ? $linkedin : null);
                $this->crearNotas($proyecto, $f);
            }

            // Contratista registrado en el CRM, si coincide el nombre exacto.
            if ($proyecto->subcontratista && !$proyecto->contratista_client_id) {
                $c = $matcher->candidatos($proyecto->subcontratista, 1)->first();
                if ($c && mb_stripos($c->name_social, $proyecto->subcontratista) !== false) {
                    $proyecto->contratista_client_id = $c->id;
                    $proyecto->save();
                }
            }
        }

        $this->info("Plan: $creados proyectos nuevos, $actualizados actualizados.");
    }

    protected function crearDecisores(AtaqueProyecto $p, $contacto, $cargo, $linkedin)
    {
        if (!$contacto) {
            return;
        }
        $nombres = array_map('trim', explode('/', $contacto));
        $cargos = array_map('trim', explode('/', (string) $cargo));
        $mismoNumero = count($nombres) === count($cargos);

        foreach ($nombres as $i => $nombre) {
            $esArea = (bool) preg_match('/^(gerencia|equipo|área|area|direcci[oó]n)\b/iu', $nombre);
            AtaqueDecisor::create([
                'ataque_proyecto_id' => $p->id,
                'nombre' => $esArea ? null : $nombre,
                'cargo' => $esArea ? trim($nombre . ($cargo ? ' · ' . $cargo : '')) : ($mismoNumero ? $cargos[$i] : $cargo),
                'empresa' => $p->empresa,
                'rol_compra' => $i === 0 ? 'decisor' : 'contacto',
                'linkedin_url' => $i === 0 ? $linkedin : null,
            ]);
        }
    }

    /** Las columnas de seguimiento del Excel pasan a ser actividades. */
    protected function crearNotas(AtaqueProyecto $p, array $f)
    {
        $columnas = [13 => 'Acción Alvaro', 14 => 'Status', 15 => 'IC', 16 => 'Stand by', 17 => 'Estado'];
        foreach ($columnas as $i => $titulo) {
            $v = isset($f[$i]) ? trim((string) $f[$i]) : '';
            if ($v === '') {
                continue;
            }
            AtaqueActividad::create([
                'ataque_proyecto_id' => $p->id,
                'tipo' => 'excel',
                'descripcion' => $titulo . ': ' . (strtoupper($v) === 'X' ? 'sí' : $v),
            ]);
        }
    }

    protected function importarBoletines(array $filas)
    {
        array_shift($filas);
        $n = 0;
        foreach ($filas as $f) {
            if (empty($f[1])) {
                continue;
            }
            $obra = trim(str_replace('**', '', $f[1]));
            ObraPublica::updateOrCreate(
                ['obra' => $obra, 'fecha' => $this->fecha($f[0])],
                [
                    'ubicacion' => $f[2] ?? null,
                    'region' => $f[3] ?? null,
                    'estado_avance' => $f[4] ?? null,
                    'decisores' => $f[5] ?? null,
                    'aceros' => $f[6] ?? null,
                ]
            );
            $n++;
        }
        $this->info("Boletines: $n obras públicas.");
    }

    protected function prioridad($texto)
    {
        $t = mb_strtolower((string) $texto);
        $mapa = [
            'inmediato' => 'inmediato', 'mediano' => 'mediano', 'seguimiento' => 'seguimiento',
            'stand' => 'stand_by', 'identificado' => 'identificado', 'revis' => 'revision',
        ];
        foreach ($mapa as $busca => $clave) {
            if (strpos($t, $busca) !== false) {
                return [$clave, $texto];
            }
        }

        // Ej. "JESSICA": cuenta asignada a una persona del equipo.
        return ['asignado', $texto];
    }

    protected function esViabilidad($v)
    {
        return (bool) preg_match('/^(alta|media|baja)/iu', trim((string) $v));
    }

    protected function inversion($texto)
    {
        if (preg_match('/(US\$|S\/)\s?[\d.,]+\s?(M|K|millones)?/iu', $texto, $m)) {
            return trim($m[0]);
        }

        return null;
    }

    protected function fecha($v)
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return ExcelDate::excelToDateTimeObject($v)->format('Y-m-d');
        }
        $ts = strtotime($v);

        return $ts ? date('Y-m-d', $ts) : null;
    }
}
