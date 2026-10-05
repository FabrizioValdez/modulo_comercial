<?php

namespace App\Models\Ataque;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AtaqueDecisor extends Model
{
    protected $table = 'ataque_decisores';

    protected $guarded = ['id'];

    protected $casts = [
        'perfil_ia' => 'array',
        'sugerido_ia' => 'boolean',
    ];

    public function proyecto()
    {
        return $this->belongsTo(AtaqueProyecto::class, 'ataque_proyecto_id');
    }

    public function getFotoUrlAttribute()
    {
        return $this->foto_path ? Storage::disk(config('ataque.fotos_disk'))->url($this->foto_path) : null;
    }

    public function getIniciales()
    {
        if (!$this->nombre) {
            return '?';
        }
        $partes = preg_split('/\s+/', trim($this->nombre));
        $ini = '';
        foreach (array_slice($partes, 0, 2) as $p) {
            $ini .= mb_strtoupper(mb_substr($p, 0, 1));
        }

        return $ini;
    }

    public function getRolLabelAttribute()
    {
        return config('ataque.roles_compra.' . $this->rol_compra, $this->rol_compra);
    }

    /** Enlace a LinkedIn: el perfil guardado o una búsqueda por nombre y empresa. */
    public function getLinkedinEnlaceAttribute()
    {
        if ($this->linkedin_url) {
            return $this->linkedin_url;
        }
        if (!$this->nombre) {
            return null;
        }
        $q = trim($this->nombre . ' ' . ($this->empresa ?: optional($this->proyecto)->empresa));

        return 'https://www.linkedin.com/search/results/all/?keywords=' . rawurlencode($q);
    }
}
