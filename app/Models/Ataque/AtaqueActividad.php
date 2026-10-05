<?php

namespace App\Models\Ataque;

use Illuminate\Database\Eloquent\Model;

class AtaqueActividad extends Model
{
    protected $table = 'ataque_actividades';

    protected $guarded = ['id'];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function proyecto()
    {
        return $this->belongsTo(AtaqueProyecto::class, 'ataque_proyecto_id');
    }
}
