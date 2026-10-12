<?php

namespace App\Console\Commands\Ataque;

use App\Models\Ataque\AtaqueDecisor;
use App\Models\Ataque\AtaqueProyecto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * php artisan ataque:importar-personas /ruta/datos-personas
 *
 * La carpeta trae personas.json y fotos/. Cruza cada persona con los decisores
 * ya cargados desde el Excel (por nombre, sin tildes) y si no existe la crea.
 * Las fotos se copian al disco público del CRM. Se puede repetir sin duplicar.
 */
class ImportarPersonas extends Command
{
    protected $signature = 'ataque:importar-personas {carpeta : Carpeta con personas.json y fotos/}';

    protected $description = 'Importa personas, cargos y fotos del reporte de inteligencia comercial';

    public function handle()
    {
        $carpeta = rtrim($this->argument('carpeta'), '/\\');
        $archivo = $carpeta . DIRECTORY_SEPARATOR . 'personas.json';
        if (!is_file($archivo)) {
            $this->error('No encuentro ' . $archivo);

            return 1;
        }
        $datos = json_decode(file_get_contents($archivo), true);
        if (!is_array($datos) || empty($datos['empresas'])) {
            $this->error('personas.json no tiene el formato esperado.');

            return 1;
        }

        $disk = Storage::disk(config('ataque.fotos_disk'));
        $creados = 0;
        $actualizados = 0;
        $fotos = 0;

        foreach ($datos['empresas'] as $e) {
            $proyectos = AtaqueProyecto::where('empresa', 'ilike', $e['empresa_like'])->get();
            if ($proyectos->isEmpty()) {
                $this->warn('Sin cuenta en el plan para ' . $e['clave'] . ' (' . $e['empresa_like'] . ')');
                continue;
            }

            foreach ($proyectos as $proyecto) {
                $proyecto->fill(array_filter([
                    'datos_empresa' => $e['datos_empresa'] ?? null,
                    'datos_fuente' => $e['fuente'] ?? null,
                    'alerta' => $e['alerta'] ?? null,
                ]))->save();

                $existentes = $proyecto->decisores()->get();

                foreach ($e['personas'] as $p) {
                    $decisor = $this->buscar($existentes, $p['nombre']);
                    $valores = [
                        'nombre' => $p['nombre'],
                        'cargo' => $p['cargo'],
                        'universidad' => $p['universidad'] ?? null,
                        'empresa' => $proyecto->empresa,
                    ];
                    if (!empty($p['nota'])) {
                        $valores['notas'] = trim(($decisor && $decisor->notas && strpos($decisor->notas, $p['nota']) === false ? $decisor->notas . "\n" : '') . $p['nota']);
                    }

                    if ($decisor) {
                        // No pisa el rol si el equipo ya lo cambió.
                        if ($decisor->rol_compra === 'contacto') {
                            $valores['rol_compra'] = $p['rol_compra'];
                        }
                        $decisor->update($valores);
                        $actualizados++;
                    } else {
                        $valores['ataque_proyecto_id'] = $proyecto->id;
                        $valores['rol_compra'] = $p['rol_compra'];
                        $decisor = AtaqueDecisor::create($valores);
                        $existentes->push($decisor);
                        $creados++;
                    }

                    if (!empty($p['foto'])) {
                        $origen = $carpeta . DIRECTORY_SEPARATOR . 'fotos' . DIRECTORY_SEPARATOR . $p['foto'];
                        if (is_file($origen)) {
                            $destino = config('ataque.fotos_carpeta') . '/' . $p['foto'];
                            $disk->put($destino, file_get_contents($origen));
                            $decisor->update(['foto_path' => $destino]);
                            $fotos++;
                        } else {
                            $this->warn('Falta la foto ' . $p['foto']);
                        }
                    }
                }

                // Los cargos genéricos del Excel ("Gerencia de...") sin nombre sobran si ya hay personas.
                $proyecto->decisores()->whereNull('nombre')->whereNull('foto_path')->delete();
            }
        }

        $this->info("Personas: $creados nuevas, $actualizados actualizadas, $fotos fotos.");

        return 0;
    }

    /** Busca por nombre sin tildes: igual, o uno contiene al otro ("Pedro Ríos" ↔ "Pedro Ríos García"). */
    protected function buscar($decisores, $nombre)
    {
        $n = $this->normalizar($nombre);
        foreach ($decisores as $d) {
            if (!$d->nombre) {
                continue;
            }
            $m = $this->normalizar($d->nombre);
            if ($m === $n || Str::startsWith($n, $m . ' ') || Str::startsWith($m, $n . ' ')) {
                return $d;
            }
        }

        return null;
    }

    protected function normalizar($s)
    {
        return trim(preg_replace('/\s+/', ' ', strtolower(Str::ascii(str_replace('.', '', $s)))));
    }
}
