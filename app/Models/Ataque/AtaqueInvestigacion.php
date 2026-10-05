<?php

namespace App\Models\Ataque;

use Illuminate\Database\Eloquent\Model;

class AtaqueInvestigacion extends Model
{
    protected $table = 'ataque_investigaciones';

    protected $guarded = ['id'];

    protected $casts = [
        'parametros' => 'array',
        'resultado' => 'array',
    ];

    public function getTieneRespuestaAttribute()
    {
        return trim((string) $this->respuesta) !== '';
    }

    public function proyecto()
    {
        return $this->belongsTo(AtaqueProyecto::class, 'ataque_proyecto_id');
    }
}
