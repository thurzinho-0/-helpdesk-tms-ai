<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('categoria')->nullable();
            $table->string('sla_sugerido')->nullable();
            $table->text('impacto_operacional')->nullable();
            $table->text('primeira_acao')->nullable();
            $table->text('resposta_usuario')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'categoria',
                'sla_sugerido',
                'impacto_operacional',
                'primeira_acao',
                'resposta_usuario',
            ]);
        });
    }
};