<?php

namespace App\Models\Ataque;

use Illuminate\Database\Eloquent\Model;

class ObraPublica extends Model
{
    protected $table = 'ataque_obras_publicas';

    protected $guarded = ['id'];

    protected $casts = [
        'fecha' => 'date',
    ];
}
