<?php

namespace App\Services\Ataque;

use Illuminate\Support\Facades\DB;

/**
 * Busca la cuenta del plan en la tabla clients del CRM.
 * Exacto por id/RUC; si no, candidatos por nombre parecido para relacionar a mano.
 */
class ClienteMatcher
{
    /** Palabras que no sirven para buscar por nombre. */
    protected $ruido = [
        'sac', 's.a.c', 's.a.c.', 'sa', 's.a', 's.a.', 'srl', 'eirl', 'saa', 'peru', 'perú', 'grupo', 'inmobiliaria',
        'inmobiliario', 'holding', 'inversiones', 'constructora', 'edificaciones', 'desarrollador', 'the', 'de', 'del',
        'la', 'las', 'los', 'y', 'market', 'animal', 'health', 'film',
    ];

    public function cliente($clientId)
    {
        if (!$clientId) {
            return null;
        }

        return DB::table('clients')->select('id', 'name_social', 'number_document', 'status')->where('id', $clientId)->first();
    }

    public function porRuc($ruc)
    {
        return DB::table('clients')->select('id', 'name_social', 'number_document', 'status')
            ->where('number_document', trim($ruc))->first();
    }

    /** Candidatos por nombre (PostgreSQL ILIKE). */
    public function candidatos($nombre, $limite = 8)
    {
        $palabras = $this->palabrasClave($nombre);
        if (empty($palabras)) {
            return collect();
        }

        $q = DB::table('clients')->select('id', 'name_social', 'number_document', 'status');
        $q->where(function ($w) use ($palabras) {
            foreach ($palabras as $p) {
                $w->orWhere('name_social', 'ilike', '%' . $p . '%');
            }
        });

        // Primero los que contienen todas las palabras.
        $todas = array_map(function ($p) {
            return "CASE WHEN name_social ILIKE '%" . str_replace("'", "''", $p) . "%' THEN 1 ELSE 0 END";
        }, $palabras);

        return $q->orderByRaw('(' . implode(' + ', $todas) . ') DESC')
            ->orderBy('status')
            ->limit($limite)
            ->get();
    }

    protected function palabrasClave($nombre)
    {
        // Quita lo que va entre paréntesis y separa por espacios, / y &.
        $nombre = preg_replace('/\(.*?\)/', ' ', mb_strtolower($nombre));
        $tokens = preg_split('/[\s\/,&]+/', $nombre);
        $salida = [];
        foreach ($tokens as $t) {
            $t = trim($t, " .-");
            if (mb_strlen($t) >= 3 && !in_array($t, $this->ruido, true)) {
                $salida[] = $t;
            }
        }

        return array_slice(array_values(array_unique($salida)), 0, 3);
    }
}
