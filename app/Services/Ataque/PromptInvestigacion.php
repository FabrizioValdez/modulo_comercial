<?php

namespace App\Services\Ataque;

use App\Models\Ataque\AtaqueProyecto;

/**
 * Arma el prompt de investigación con los datos de la ficha.
 * No llama a ninguna IA: el usuario lo copia, lo usa donde quiera
 * y pega la respuesta en la ficha.
 */
class PromptInvestigacion
{
    /**
     * @param  int[]     $decisorIds  decisores a investigar (vacío = todos)
     * @param  string[]  $fuentes     claves de config('ataque.fuentes')
     */
    public function generar(AtaqueProyecto $p, array $decisorIds = [], array $fuentes = [], int $dias = 90)
    {
        $p->loadMissing('decisores');

        $decisores = $p->decisores;
        if (!empty($decisorIds)) {
            $decisores = $decisores->whereIn('id', $decisorIds);
        }

        $personas = [];
        foreach ($decisores as $d) {
            $linea = '- ' . ($d->nombre ?: 'Nombre por identificar: búscalo') . ': ' . ($d->cargo ?: 'cargo sin dato');
            if ($d->empresa) {
                $linea .= ', ' . $d->empresa;
            }
            if ($d->estado_contacto) {
                $linea .= ' (' . $d->estado_contacto . ')';
            }
            if ($d->linkedin_url) {
                $linea .= ' · LinkedIn: ' . $d->linkedin_url;
            }
            $personas[] = $linea;
        }
        if (empty($personas)) {
            $personas[] = '- Ninguna registrada: identifica a los decisores de la compra.';
        }

        $catalogo = config('ataque.fuentes');
        if (empty($fuentes)) {
            $fuentes = config('ataque.fuentes_default');
        }
        $lineasFuentes = [];
        foreach ($fuentes as $f) {
            if (isset($catalogo[$f])) {
                $lineasFuentes[] = '- ' . $catalogo[$f];
            }
        }

        $ultima = $p->actividades()->first();
        $hoy = now()->format('d/m/Y');

        $proyecto = array_filter([
            '- Empresa: ' . $p->empresa,
            '- Proyecto: ' . $p->proyecto,
            $p->inversion ? '- Inversión: ' . $p->inversion : null,
            $p->subcontratista ? '- Contratista conocido: ' . $p->subcontratista : null,
            $p->ejecucion ? '- Ejecución estimada: ' . $p->ejecucion : null,
            $p->resena ? '- Reseña: ' . $p->resena : null,
            $p->materiales ? '- Lo que queremos vender: ' . $p->materiales : null,
            $ultima ? '- Último contacto nuestro: ' . $ultima->descripcion : null,
        ]);

        return 'Eres analista de inteligencia comercial de ' . config('ataque.empresa_vendedora') . '. '
            . 'Usa búsqueda web para investigar este proyecto y a las personas vinculadas. Hoy es ' . $hoy . ".\n\n"
            . "PROYECTO\n" . implode("\n", $proyecto) . "\n\n"
            . "PERSONAS\n" . implode("\n", $personas) . "\n\n"
            . "FUENTES PRIORITARIAS\n" . implode("\n", $lineasFuentes) . "\n\n"
            . "QUÉ NECESITO\n"
            . "1. Estado actual: etapa (licencia, licitación, obra gruesa, montaje, terminado), % de avance e hitos con fecha.\n"
            . '2. Novedades de los últimos ' . $dias . " días sobre la obra, la empresa y el contratista.\n"
            . "3. Quién decide la compra de acero: propietario, contratista general y subcontratistas de estructuras, con nombres y cargos.\n"
            . "4. Por cada persona: cargo actual y desde cuándo, trayectoria resumida, publicaciones o declaraciones públicas recientes, temas que le importan y puntos de conexión (gremios, eventos, universidades).\n"
            . "5. Ángulo comercial: qué ofrecer, a quién, en qué momento y qué riesgos hay (competidor, retraso, crédito).\n"
            . "6. Un resumen de 2 a 3 frases de cómo está el proyecto HOY.\n\n"
            . "REGLAS\n"
            . "- Solo información pública. No inventes: si no encuentras un dato, escribe \"sin dato\".\n"
            . "- Cada hallazgo con URL y fecha de la fuente, y confianza alta / media / baja.\n"
            . "- No incluyas datos personales privados (teléfono o dirección personal, familia, salud).\n"
            . "- Responde solo con un JSON con esta forma:\n"
            . "{\n"
            . "  \"resumen_actual\": \"...\",\n"
            . "  \"estado_proyecto\": {\"etapa\": \"\", \"avance\": \"\", \"ultimo_hito\": \"\", \"fecha\": \"\", \"fuente\": \"\"},\n"
            . "  \"novedades\": [{\"fecha\": \"\", \"titulo\": \"\", \"resumen\": \"\", \"url\": \"\"}],\n"
            . "  \"mapa_decision\": [{\"rol\": \"\", \"empresa\": \"\", \"nombre\": \"\", \"cargo\": \"\", \"fuente\": \"\"}],\n"
            . "  \"personas\": [{\"nombre\": \"\", \"cargo\": \"\", \"desde\": \"\", \"trayectoria\": \"\", \"publicaciones\": \"\", \"temas\": \"\", \"conexiones\": \"\", \"como_abordar\": \"\", \"linkedin\": \"\", \"fuentes\": [], \"confianza\": \"\"}],\n"
            . "  \"angulo_comercial\": {\"que\": \"\", \"a_quien\": \"\", \"cuando\": \"\", \"riesgos\": \"\"},\n"
            . "  \"fuentes\": [{\"titulo\": \"\", \"medio\": \"\", \"fecha\": \"\", \"url\": \"\"}]\n"
            . '}';
    }

    /**
     * Intenta leer el JSON de una respuesta pegada (con o sin ```json).
     * Devuelve null si no es JSON válido; la respuesta igual se guarda como texto.
     */
    public function leerRespuesta($texto)
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return null;
        }
        if (preg_match('/```(?:json)?\s*(.*?)```/s', $texto, $m)) {
            $texto = trim($m[1]);
        }
        $ini = strpos($texto, '{');
        $fin = strrpos($texto, '}');
        if ($ini === false || $fin === false || $fin <= $ini) {
            return null;
        }
        $json = json_decode(substr($texto, $ini, $fin - $ini + 1), true);

        return is_array($json) ? $json : null;
    }
}
