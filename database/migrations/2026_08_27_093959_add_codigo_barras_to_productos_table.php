<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Nullable: no todo producto de una bodega trae código impreso
            // (a granel, empaques propios). Único e indexado porque es la
            // vía de búsqueda del lector en el punto de venta.
            $table->string('codigo_barras', 32)->nullable()->unique()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique(['codigo_barras']);
            $table->dropColumn('codigo_barras');
        });
    }
};
