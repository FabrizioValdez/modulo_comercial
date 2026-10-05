<?php

namespace App\Http\Controllers\Ataque;

use App\Http\Controllers\Controller;
use App\Models\Ataque\AtaqueDecisor;
use App\Models\Ataque\AtaqueProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DecisorController extends Controller
{
    public function store(Request $request, AtaqueProyecto $proyecto)
    {
        $datos = $this->validar($request);
        $datos['ataque_proyecto_id'] = $proyecto->id;
        $datos['empresa'] = $datos['empresa'] ?? $proyecto->empresa;

        $decisor = AtaqueDecisor::create($datos);
        $this->guardarFoto($request, $decisor);

        return back()->with('ok', 'Decisor agregado.');
    }

    public function update(Request $request, AtaqueDecisor $decisor)
    {
        $datos = $this->validar($request);
        $datos['sugerido_ia'] = false; // al editarlo, el equipo lo confirma
        $decisor->update($datos);
        $this->guardarFoto($request, $decisor);

        return back()->with('ok', 'Decisor actualizado.');
    }

    public function destroy(AtaqueDecisor $decisor)
    {
        if ($decisor->foto_path) {
            Storage::disk(config('ataque.fotos_disk'))->delete($decisor->foto_path);
        }
        $decisor->delete();

        return back()->with('ok', 'Decisor eliminado.');
    }

    protected function validar(Request $request)
    {
        return $request->validate([
            'nombre' => 'nullable|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'empresa' => 'nullable|string|max:255',
            'rol_compra' => 'required|in:' . implode(',', array_keys(config('ataque.roles_compra'))),
            'linkedin_url' => 'nullable|url|max:500',
            'estado_contacto' => 'nullable|string|max:60',
            'notas' => 'nullable|string|max:5000',
            'foto' => 'nullable|image|max:4096',
        ]);
    }

    protected function guardarFoto(Request $request, AtaqueDecisor $decisor)
    {
        if (!$request->hasFile('foto')) {
            return;
        }
        $disk = Storage::disk(config('ataque.fotos_disk'));
        if ($decisor->foto_path) {
            $disk->delete($decisor->foto_path);
        }
        $ruta = $request->file('foto')->store(config('ataque.fotos_carpeta'), config('ataque.fotos_disk'));
        $decisor->update(['foto_path' => $ruta]);
    }
}
