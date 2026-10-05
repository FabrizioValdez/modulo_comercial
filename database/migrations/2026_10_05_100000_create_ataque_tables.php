<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAtaqueTables extends Migration
{
    public function up()
    {
        Schema::create('ataque_proyectos', function (Blueprint $table) {
            $table->id();
            // Cliente del CRM (tabla clients). Se llena al relacionar por RUC.
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('contratista_client_id')->nullable()->index();
            $table->string('empresa');
            $table->string('subcontratista')->nullable();
            $table->string('rubro')->nullable();
            $table->text('proyecto');
            $table->text('resena')->nullable();
            $table->string('prioridad', 30)->default('identificado')->index();
            $table->string('prioridad_texto')->nullable();
            $table->string('viabilidad', 60)->nullable();
            $table->string('ejecucion', 120)->nullable();
            $table->string('inversion', 60)->nullable();
            $table->text('materiales')->nullable();
            $table->text('accion_comercial')->nullable();
            $table->text('proximo_paso')->nullable();
            $table->string('busqueda_linkedin', 500)->nullable();
            $table->text('resumen_ia')->nullable();
            $table->timestamp('resumen_ia_at')->nullable();
            $table->string('origen', 60)->default('excel');
            $table->timestamps();
        });

        Schema::create('ataque_decisores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ataque_proyecto_id')->constrained('ataque_proyectos')->cascadeOnDelete();
            // Contacto del CRM (tabla contacts), opcional.
            $table->unsignedBigInteger('contact_id')->nullable()->index();
            $table->string('nombre')->nullable();
            $table->string('cargo')->nullable();
            $table->string('empresa')->nullable();
            $table->string('rol_compra', 30)->default('contacto');
            $table->string('linkedin_url', 500)->nullable();
            $table->string('foto_path')->nullable();
            $table->string('estado_contacto', 60)->nullable();
            $table->json('perfil_ia')->nullable();
            $table->boolean('sugerido_ia')->default(false);
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('ataque_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ataque_proyecto_id')->constrained('ataque_proyectos')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('fecha')->nullable();
            $table->string('tipo', 40)->default('nota');
            $table->text('descripcion');
            $table->timestamps();
        });

        Schema::create('ataque_investigaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ataque_proyecto_id')->constrained('ataque_proyectos')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('parametros')->nullable();
            $table->longText('prompt');
            // Respuesta pegada por el usuario (texto libre o JSON).
            $table->longText('respuesta')->nullable();
            $table->json('resultado')->nullable();
            $table->string('herramienta', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('ataque_obras_publicas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->nullable();
            $table->string('obra');
            $table->string('ubicacion')->nullable();
            $table->string('region', 80)->nullable();
            $table->text('estado_avance')->nullable();
            $table->text('decisores')->nullable();
            $table->text('aceros')->nullable();
            $table->foreignId('ataque_proyecto_id')->nullable()->constrained('ataque_proyectos')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ataque_obras_publicas');
        Schema::dropIfExists('ataque_investigaciones');
        Schema::dropIfExists('ataque_actividades');
        Schema::dropIfExists('ataque_decisores');
        Schema::dropIfExists('ataque_proyectos');
    }
}
