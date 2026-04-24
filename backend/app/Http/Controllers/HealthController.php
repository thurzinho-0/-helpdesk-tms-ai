<?php

namespace App\Http\Controllers;

class HealthController extends Controller
{
    public function check()
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'API funcionando (controller)'
        ]);
    }
}
// Controller responsável por separar a lógica da aplicação das rotas.
// Aqui tratamos a requisição e retornamos a resposta da API de forma organizada.