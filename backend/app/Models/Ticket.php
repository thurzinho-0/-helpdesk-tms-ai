<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 🚀 Modelo Ticket - Sistema de Chamados (com IA)
 */
class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descricao',
        'prioridade',
        'status',
        'categoria',
        'sla_sugerido',
        'impacto_operacional',
        'primeira_acao',
        'resposta_usuario',
    ];

    protected $casts = [
        'prioridade' => 'string',
        'status' => 'string',
    ];
}