<?php

namespace App\Models\Ataque;

use Illuminate\Database\Eloquent\Model;

class AtaqueProyecto extends Model
{
    protected $table = 'ataque_proyectos';

    protected $guarded = ['id'];

    protected $casts = [
        'resumen_ia_at' => 'datetime',
    ];

    public function decisores()
    {
        return $this->hasMany(AtaqueDecisor::class, 'ataque_proyecto_id')
            ->orderByRaw("CASE rol_compra WHEN 'decisor' THEN 0 WHEN 'influenciador' THEN 1 WHEN 'tecnico' THEN 2 WHEN 'compras' THEN 3 ELSE 4 END");
    }

    public function actividades()
    {
        return $this->hasMany(AtaqueActividad::class, 'ataque_proyecto_id')
            ->orderByDesc('fecha')->orderByDesc('id');
    }

    public function investigaciones()
    {
        return $this->hasMany(AtaqueInvestigacion::class, 'ataque_proyecto_id')->orderByDesc('id');
    }

    public function ultimaInvestigacion()
    {
        return $this->hasOne(AtaqueInvestigacion::class, 'ataque_proyecto_id')->latest('id');
    }

    public function getPrioridadLabelAttribute()
    {
        return config('ataque.prioridades.' . $this->prioridad, $this->prioridad_texto ?: $this->prioridad);
    }

    public function getDecisorPrincipalAttribute()
    {
        return $this->decisores->first();
    }

    /** Lista de materiales separada por comas, para mostrar como etiquetas. */
    public function getMaterialesListaAttribute()
    {
        if (!$this->materiales) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/,(?![^(]*\))/', $this->materiales))));
    }
}
