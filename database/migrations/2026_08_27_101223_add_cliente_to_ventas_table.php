<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Datos del comprador guardados en la propia venta, no en una
            // tabla de clientes: una bodega vende sobre todo a público
            // general y solo pide los datos cuando le piden comprobante.
            $table->string('cliente_nombre')->nullable()->after('user_id');
            $table->string('cliente_documento', 20)->nullable()->after('cliente_nombre');

            $table->index('cliente_documento');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['cliente_documento']);
            $table->dropColumn(['cliente_nombre', 'cliente_documento']);
        });
    }
};
