<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Models\Ticket;
use App\Services\OpenAIService;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->prioridade) {
            $query->where('prioridade', $request->prioridade);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string',
            'prioridade' => ['nullable', Rule::in(['BAIXA', 'MEDIA', 'ALTA'])]
        ]);

        $ticket = Ticket::create([
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'prioridade' => $request->prioridade ?? 'MEDIA',
            'status' => 'ABERTO'
        ]);

        // 🤖 IA automática
        try {
            $analysis = app(OpenAIService::class)->triageTicket(
                $ticket->titulo,
                $ticket->descricao
            );

            $ticket->update([
                'categoria'           => $analysis['categoria'] ?? null,
                'prioridade'          => $analysis['prioridade_sugerida'] ?? $ticket->prioridade,
                'sla_sugerido'        => $analysis['sla_sugerido'] ?? null,
                'impacto_operacional' => $analysis['impacto_operacional'] ?? null,
                'primeira_acao'       => $analysis['primeira_acao'] ?? null,
                'resposta_usuario'    => $analysis['resposta_usuario'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro na triagem IA: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data' => $ticket->fresh(),
            'message' => 'Ticket criado com sucesso (com IA)'
        ], 201);
    }

    public function show($id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Ticket::findOrFail($id)
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['ABERTO', 'EM_ANDAMENTO', 'RESOLVIDO'])]
        ]);

        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'data' => $ticket,
            'message' => 'Status atualizado'
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticket deletado com sucesso'
        ]);
    }

    public function triage($id, OpenAIService $ai): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        $analysis = $ai->triageTicket(
            $ticket->titulo,
            $ticket->descricao
        );

        $ticket->update([
            'categoria'           => $analysis['categoria'] ?? null,
            'prioridade'          => $analysis['prioridade_sugerida'] ?? $ticket->prioridade,
            'sla_sugerido'        => $analysis['sla_sugerido'] ?? null,
            'impacto_operacional' => $analysis['impacto_operacional'] ?? null,
            'primeira_acao'       => $analysis['primeira_acao'] ?? null,
            'resposta_usuario'    => $analysis['resposta_usuario'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $analysis,
            'message' => 'Triagem IA realizada com sucesso'
        ]);
    }
}