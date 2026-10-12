<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInteligenciaToAtaqueTables extends Migration
{
    public function up()
    {
        Schema::table('ataque_proyectos', function (Blueprint $table) {
            $table->text('datos_empresa')->nullable()->after('resena');
            $table->string('datos_fuente')->nullable()->after('datos_empresa');
            $table->string('alerta', 500)->nullable()->after('datos_fuente');
        });

        Schema::table('ataque_decisores', function (Blueprint $table) {
            $table->string('universidad')->nullable()->after('empresa');
        });
    }

    public function down()
    {
        Schema::table('ataque_decisores', function (Blueprint $table) {
            $table->dropColumn('universidad');
        });
        Schema::table('ataque_proyectos', function (Blueprint $table) {
            $table->dropColumn(['datos_empresa', 'datos_fuente', 'alerta']);
        });
    }
}
