<?php

namespace App\Services\Ataque;

use App\Models\Ataque\AtaqueProyecto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Junta noticias de varias fuentes (RSS) sobre la empresa y el proyecto.
 * Las fuentes están en config('ataque.noticias'). Solo lee titulares y enlaces públicos.
 */
class NoticiasService
{
    public function deProyecto(AtaqueProyecto $p)
    {
        $empresa = $this->nombreCorto($p->empresa);
        $consultas = ['"' . $empresa . '"'];
        $clave = $this->palabraProyecto($p->proyecto);
        if ($clave) {
            $consultas[] = '"' . $empresa . '" ' . $clave;
        }

        return $this->buscar($consultas, [$empresa], 'p' . $p->id);
    }

    public function dePersona($nombre, $empresa)
    {
        $empresa = $this->nombreCorto($empresa);

        return $this->buscar(['"' . $nombre . '" ' . $empresa], [$nombre], 'n' . md5($nombre . $empresa));
    }

    /**
     * @param string[] $consultas  búsquedas para las fuentes con {q}
     * @param string[] $filtro     palabras que deben aparecer en los feeds generales
     */
    protected function buscar(array $consultas, array $filtro, $claveCache)
    {
        $cfg = config('ataque.noticias');

        return Cache::remember('ataque.noticias.' . $claveCache, now()->addMinutes($cfg['cache_minutos']), function () use ($cfg, $consultas, $filtro) {
            $items = [];
            foreach ($cfg['busquedas'] as $fuente) {
                foreach ($consultas as $q) {
                    $url = str_replace('{q}', rawurlencode($q), $fuente['url']);
                    $items = array_merge($items, $this->leerRss($url, $fuente['nombre'], $cfg['max_por_fuente']));
                }
            }
            foreach ($cfg['feeds'] as $nombre => $url) {
                foreach ($this->leerRss($url, $nombre, 50) as $it) {
                    foreach ($filtro as $palabra) {
                        if (mb_stripos($it['titulo'] . ' ' . $it['resumen'], $palabra) !== false) {
                            $items[] = $it;
                            break;
                        }
                    }
                }
            }

            // Sin duplicados (mismo titular) y lo más reciente primero.
            $vistos = [];
            $unicos = [];
            foreach ($items as $it) {
                $k = mb_strtolower(preg_replace('/\W+/u', '', $it['titulo']));
                if ($k === '' || isset($vistos[$k])) {
                    continue;
                }
                $vistos[$k] = true;
                $unicos[] = $it;
            }
            usort($unicos, function ($a, $b) {
                return ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0);
            });

            return [
                'actualizado' => now()->toIso8601String(),
                'items' => array_slice($unicos, 0, $cfg['max_total']),
            ];
        });
    }

    protected function leerRss($url, $fuente, $max)
    {
        try {
            $resp = Http::timeout(8)->withHeaders(['User-Agent' => 'Mozilla/5.0 (CRM Plan de Ataque)'])->get($url);
            if (!$resp->successful()) {
                return [];
            }
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($resp->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
            if (!$xml || !isset($xml->channel->item)) {
                return [];
            }
            $salida = [];
            foreach ($xml->channel->item as $item) {
                $link = trim((string) $item->link);
                if (!preg_match('#^https?://#i', $link)) {
                    continue;
                }
                $ts = strtotime((string) $item->pubDate) ?: null;
                $medio = trim((string) $item->source) ?: $fuente;
                $salida[] = [
                    'titulo' => html_entity_decode(trim((string) $item->title), ENT_QUOTES, 'UTF-8'),
                    'url' => $link,
                    'medio' => $medio,
                    'via' => $fuente,
                    'fecha' => $ts ? date('Y-m-d', $ts) : null,
                    'ts' => $ts,
                    'resumen' => mb_substr(trim(strip_tags(html_entity_decode((string) $item->description, ENT_QUOTES, 'UTF-8'))), 0, 240),
                ];
                if (count($salida) >= $max) {
                    break;
                }
            }

            return $salida;
        } catch (\Throwable $e) {
            Log::info('Ataque: fuente de noticias no disponible', ['fuente' => $fuente, 'error' => $e->getMessage()]);

            return [];
        }
    }

    /** "OPP Film (Oben Holding)" → "OPP Film"; "Mapei Perú" → "Mapei". */
    public function nombreCorto($empresa)
    {
        $e = trim(preg_replace('/\(.*?\)/', '', (string) $empresa));
        $e = preg_replace('/\s+(Per[uú]|S\.?A\.?C?\.?|Inmobiliaria|Grupo Inmobiliario)$/iu', '', $e);

        return trim(explode('/', $e)[0]);
    }

    /** Una palabra distintiva del proyecto para afinar la búsqueda (ej. "Lurín", "MegaPlaza"). */
    protected function palabraProyecto($proyecto)
    {
        if (preg_match('/\b(Lur[ií]n|Chilca|Callao|Huaral|Chancay|Ate|Sullana|Quillabamba|Paita|MegaPlaza|Los Olivos|Miraflores|Surco|San Isidro|Arequipa)\b/iu', (string) $proyecto, $m)) {
            return $m[1];
        }

        return null;
    }
}
