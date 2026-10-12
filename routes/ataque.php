<?php

use App\Http\Controllers\Ataque\DecisorController;
use App\Http\Controllers\Ataque\InvestigacionController;
use App\Http\Controllers\Ataque\MovilController;
use App\Http\Controllers\Ataque\PlanController;
use Illuminate\Support\Facades\Route;

/*
| Incluir desde routes/web.php:
|     require __DIR__ . '/ataque.php';
*/

Route::middleware(config('ataque.middleware'))
    ->prefix(config('ataque.prefix'))
    ->name('ataque.')
    ->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/proyectos/{proyecto}', [PlanController::class, 'show'])->name('show');
        Route::post('/proyectos/{proyecto}/relacionar', [PlanController::class, 'relacionar'])->name('relacionar');
        Route::post('/proyectos/{proyecto}/actividades', [PlanController::class, 'actividad'])->name('actividad');

        Route::post('/proyectos/{proyecto}/decisores', [DecisorController::class, 'store'])->name('decisores.store');
        Route::put('/decisores/{decisor}', [DecisorController::class, 'update'])->name('decisores.update');
        Route::delete('/decisores/{decisor}', [DecisorController::class, 'destroy'])->name('decisores.destroy');

        Route::get('/proyectos/{proyecto}/investigar', [InvestigacionController::class, 'create'])->name('investigar');
        Route::post('/proyectos/{proyecto}/investigar', [InvestigacionController::class, 'store'])->name('investigacion.store');
        Route::get('/investigaciones/{investigacion}', [InvestigacionController::class, 'show'])->name('investigacion.show');

        // App móvil (PWA)
        Route::get('/movil', [MovilController::class, 'index'])->name('movil');
        Route::get('/movil/datos', [MovilController::class, 'datos'])->name('movil.datos');
        Route::get('/movil/noticias/{proyecto}', [MovilController::class, 'noticias'])->name('movil.noticias');
        Route::get('/movil/noticias-persona/{decisor}', [MovilController::class, 'noticiasPersona'])->name('movil.noticias.persona');
        Route::get('/movil/manifest.json', [MovilController::class, 'manifest'])->name('movil.manifest');
        Route::get('/sw-movil.js', [MovilController::class, 'serviceWorker'])->name('movil.sw');
    });
