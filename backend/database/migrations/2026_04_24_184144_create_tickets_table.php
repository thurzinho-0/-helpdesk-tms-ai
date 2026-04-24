<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🚀 Cria tabela TICKETS - Sistema de Chamados
     * 
     * 📚 Inspirado em: Bonieky Lacerda (Laravel API)
     * 
     * Campos:
     * ✅ id (auto)
     * ✅ titulo (string)
     * ✅ descricao (text) 
     * ✅ prioridade (BAIXA/MEDIA/ALTA)
     * ✅ status (ABERTO/EM_ANDAMENTO/RESOLVIDO)
     * ✅ timestamps (created/updated)
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            
            $table->string('titulo');
            $table->text('descricao');
            
            $table->enum('prioridade', ['BAIXA', 'MEDIA', 'ALTA'])->default('MEDIA');
            $table->enum('status', ['ABERTO', 'EM_ANDAMENTO', 'RESOLVIDO'])->default('ABERTO');
            
            $table->timestamps();
        });
    }

    /**
     * 🗑️ Remove tabela se der ruim
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};