<?php

namespace App\Services\Ataque;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Facturación del año en curso desde el ERP (SQL Server), por RUC.
 * Neto sin IGV en soles (subtotal_loc), restando notas de crédito.
 */
class ErpFacturacion
{
    public function resumenActual($ruc)
    {
        $ruc = trim((string) $ruc);
        if ($ruc === '') {
            return null;
        }
        $anio = (int) date('Y');
        $clave = 'ataque.erp.' . $ruc . '.' . $anio;

        return Cache::remember($clave, now()->addMinutes(config('ataque.cache_minutos', 30)), function () use ($ruc, $anio) {
            try {
                return $this->consultar($ruc, $anio);
            } catch (\Throwable $e) {
                Log::warning('Ataque: no se pudo leer la facturación del ERP', ['ruc' => $ruc, 'error' => $e->getMessage()]);

                return ['error' => true, 'anio' => $anio];
            }
        });
    }

    protected function consultar($ruc, $anio)
    {
        $fact = $this->lista(config('ataque.tipos_factura'));
        $nc = $this->lista(config('ataque.tipos_nota_credito'));
        $todos = $fact . ',' . $nc;
        $erp = DB::connection(config('ataque.erp_connection'));

        $fila = $erp->selectOne(
            "SELECT
                COUNT(CASE WHEN c.id_tipocomprobante IN ($fact) THEN 1 END) AS facturas,
                SUM(CASE WHEN c.id_tipocomprobante IN ($nc) THEN -c.subtotal_loc ELSE c.subtotal_loc END) AS neto,
                MAX(CASE WHEN c.id_tipocomprobante IN ($fact) THEN c.fecha END) AS ultima
             FROM comprobante c
             JOIN cliente cl ON cl.id = c.id_cliente
             WHERE cl.numerodocumento = ?
               AND c.fecha_anulacion IS NULL
               AND c.id_tipocomprobante IN ($todos)
               AND c.fecha >= ? AND c.fecha < ?",
            [$ruc, $anio . '-01-01', ($anio + 1) . '-01-01']
        );

        // Saldo pendiente de cualquier año: es lo que el cliente debe hoy.
        $pendiente = $erp->selectOne(
            "SELECT SUM(c.total_pendiente) AS pendiente
             FROM comprobante c
             JOIN cliente cl ON cl.id = c.id_cliente
             WHERE cl.numerodocumento = ?
               AND c.fecha_anulacion IS NULL
               AND c.id_tipocomprobante IN ($fact)
               AND c.total_pendiente > 0",
            [$ruc]
        );

        $existe = $erp->selectOne('SELECT TOP 1 id FROM cliente WHERE numerodocumento = ?', [$ruc]);

        return [
            'error' => false,
            'anio' => $anio,
            'en_erp' => (bool) $existe,
            'facturas' => (int) ($fila->facturas ?? 0),
            'neto' => (float) ($fila->neto ?? 0),
            'ultima' => $fila->ultima ?? null,
            'pendiente' => (float) ($pendiente->pendiente ?? 0),
        ];
    }

    /** Cotizaciones del año en el CRM (tabla quotations). */
    public function cotizacionesActuales($clientId)
    {
        if (!$clientId) {
            return null;
        }
        $anio = (int) date('Y');
        $q = DB::table('quotations')->where('client_id', $clientId)->whereYear('date_presentation', $anio);

        return [
            'anio' => $anio,
            'cantidad' => (clone $q)->count(),
            'ultima' => (clone $q)->max('date_presentation'),
        ];
    }

    protected function lista(array $ids)
    {
        return implode(',', array_map('intval', $ids));
    }
}
