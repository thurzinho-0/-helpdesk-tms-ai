<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAIService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');

        if (!$this->apiKey) {
            throw new Exception('OPENAI_API_KEY não configurada');
        }
    }

    public function triageTicket(string $titulo, string $descricao): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, [
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0.1,
                    'max_tokens' => 500,
                    'messages' => $this->buildMessages($titulo, $descricao)
                ]);

            if ($response->failed()) {
                Log::error('OpenAI Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return $this->fallbackResponse();
            }

            $data = $response->json('choices.0.message.content', '');

            preg_match('/\{.*\}/s', $data, $matches);

            $json = json_decode($matches[0] ?? '{}', true);

            return $this->sanitizeResponse($json ?? []);
        } catch (Exception $e) {
            Log::error('OpenAI Exception: ' . $e->getMessage());

            return $this->fallbackResponse();
        }
    }

    private function buildMessages(string $titulo, string $descricao): array
    {
        return [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt()
            ],
            [
                'role' => 'user',
                'content' => "Título: {$titulo}\nDescrição: {$descricao}"
            ]
        ];
    }

    private function getSystemPrompt(): string
    {
        return '
Você é um Analista de Suporte N1 especializado em TMS e operação logística.

Retorne SOMENTE um JSON válido com os campos:
categoria, prioridade_sugerida, impacto_operacional, sla_sugerido, primeira_acao, resposta_usuario.

Categorias permitidas:
ACESSO, ERRO_SISTEMA, PARAMETRIZACAO, OPERACAO_LOGISTICA, TREINAMENTO, INTEGRACAO, OUTROS.

Prioridades permitidas:
BAIXA, MEDIA, ALTA.

Regras:
- Se impedir operação logística, prioridade ALTA.
- Se afetar múltiplos usuários, prioridade ALTA.
- Se for dúvida simples de uso, prioridade BAIXA.
- Se for erro de acesso individual, prioridade MEDIA.
';
    }

    private function sanitizeResponse(array $data): array
    {
        $categorias = [
            'ACESSO',
            'ERRO_SISTEMA',
            'PARAMETRIZACAO',
            'OPERACAO_LOGISTICA',
            'TREINAMENTO',
            'INTEGRACAO',
            'OUTROS'
        ];

        $prioridades = ['BAIXA', 'MEDIA', 'ALTA'];

        return [
            'categoria' => in_array($data['categoria'] ?? '', $categorias)
                ? $data['categoria']
                : 'OUTROS',

            'prioridade_sugerida' => in_array($data['prioridade_sugerida'] ?? '', $prioridades)
                ? $data['prioridade_sugerida']
                : 'MEDIA',

            'impacto_operacional' => $data['impacto_operacional'] ?? 'Impacto não identificado',

            'sla_sugerido' => $data['sla_sugerido'] ?? '4 horas',

            'primeira_acao' => $data['primeira_acao'] ?? 'Analisar chamado manualmente',

            'resposta_usuario' => $data['resposta_usuario'] ?? 'Recebemos seu chamado e ele será analisado pelo suporte.'
        ];
    }

    private function fallbackResponse(): array
    {
        return [
            'categoria' => 'OUTROS',
            'prioridade_sugerida' => 'MEDIA',
            'impacto_operacional' => 'Não foi possível analisar automaticamente',
            'sla_sugerido' => '4 horas',
            'primeira_acao' => 'Encaminhar para análise manual do suporte',
            'resposta_usuario' => 'Recebemos seu chamado e ele será analisado pela equipe de suporte.'
        ];
    }
}