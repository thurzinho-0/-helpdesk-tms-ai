# HelpDesk TMS com IA

<img width="886" height="477" alt="image" src="https://github.com/user-attachments/assets/84a26187-8212-42f9-8293-c623348c402c" />

Sistema de gerenciamento de chamados com **triagem automática via LLM**. Recebe um ticket em linguagem natural e retorna categoria, prioridade, SLA sugerido, impacto operacional e primeira ação recomendada — pronto para o time de suporte agir.

**Stack:** Laravel 11 · SQLite · OpenAI API · HTML/CSS/JS

---

## O problema

Equipes de suporte N1 gastam de 3 a 5 minutos por ticket apenas classificando e priorizando chamados antes de começar o atendimento. Em operações logísticas, esse tempo significa SLA estourado, cliente irritado e caminhão parado no pátio.

## A solução

Uma API que usa um LLM para automatizar a triagem no momento da criação do ticket:

- **Classifica** em categorias operacionais (`OPERACAO_LOGISTICA`, `TI_INFRAESTRUTURA`, `FINANCEIRO`, etc.)
- **Define prioridade** (BAIXA / MÉDIA / ALTA) com base no impacto descrito
- **Sugere SLA** realista para o tipo de problema
- **Analisa impacto operacional** em linguagem natural
- **Gera primeira ação** para o analista N1 executar
- **Redige resposta** automática para o solicitante

Resultado: N1 recebe o ticket já triado, pula a fase de análise inicial e começa direto no problema.

---

## Demonstração

**Entrada** (texto livre do usuário):

> "A expedição está parada desde as 8h. O TMS não gera as notas e temos 40 caminhões aguardando carregamento."

**Saída** gerada pela IA:

| Campo | Valor |
|---|---|
| Categoria | `OPERACAO_LOGISTICA` |
| Prioridade | `ALTA` |
| SLA sugerido | `1 hora` |
| Impacto operacional | Bloqueio crítico da expedição. 40 veículos parados geram custo de demurrage e risco de quebra de SLA com clientes finais. |
| Primeira ação | Verificar integração TMS ↔ ERP, validar fila de notas fiscais e acionar N2 caso persista após 15min. |
| Resposta ao usuário | "Olá! Recebemos seu chamado e já o classificamos como prioridade alta. Nossa equipe está investigando a integração do TMS e retornaremos em até 1h com um diagnóstico." |

---

## Arquitetura

```
┌──────────────┐      HTTP       ┌──────────────┐       ┌──────────────┐
│   Frontend   │ ───────────────▶│   Laravel    │ ─────▶│  OpenAI API  │
│ (HTML/JS/CSS)│      JSON       │   REST API   │       │              │
└──────────────┘ ◀───────────────└──────┬───────┘◀──────└──────────────┘
                                        │
                                        ▼
                                 ┌──────────────┐
                                 │    SQLite    │
                                 └──────────────┘
```

**Fluxo de triagem:**

1. Frontend envia `POST /api/tickets` com título e descrição
2. Controller persiste o ticket com status `ABERTO`
3. `OpenAIService` é disparado com o contexto do chamado
4. LLM retorna JSON estruturado com os 6 campos
5. Ticket é atualizado com a triagem e retornado ao frontend
6. Frontend renderiza o resultado classificado

---

## Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/api/tickets` | Lista todos os tickets |
| `POST` | `/api/tickets` | Cria ticket e dispara triagem automática |
| `GET` | `/api/tickets/{id}` | Detalha um ticket |
| `PUT` | `/api/tickets/{id}` | Atualiza ticket |
| `DELETE` | `/api/tickets/{id}` | Remove ticket |
| `POST` | `/api/tickets/{id}/triage` | Reanalisa com IA |

### Exemplo de payload

```json
POST /api/tickets
{
  "titulo": "Expedição parada no TMS",
  "descricao": "Sistema não gera notas desde as 8h, 40 veículos aguardando."
}
```

### Exemplo de resposta

```json
{
  "id": 42,
  "titulo": "Expedição parada no TMS",
  "descricao": "Sistema não gera notas desde as 8h...",
  "status": "ABERTO",
  "prioridade": "ALTA",
  "categoria": "OPERACAO_LOGISTICA",
  "sla_sugerido": "1 hora",
  "impacto_operacional": "Bloqueio crítico...",
  "primeira_acao": "Verificar integração TMS...",
  "resposta_usuario": "Olá! Recebemos seu chamado...",
  "created_at": "2026-04-24T12:00:00Z"
}
```

---

## Como rodar

### Pré-requisitos

- PHP 8.2+
- Composer
- Chave da OpenAI API

### Instalação

```bash
git clone https://github.com/arthurmarques/helpdesk-tms-ia.git
cd helpdesk-tms-ia/backend

composer install
cp .env.example .env
php artisan key:generate
```

### Configure a chave da OpenAI

Edite o `.env`:

```env
OPENAI_API_KEY=sk-sua-chave-aqui
OPENAI_MODEL=gpt-4o-mini
```

### Prepare o banco e suba o servidor

```bash
php artisan migrate
php artisan serve
```

Acesse:

- **API:** `http://127.0.0.1:8000/api/tickets`
- **Frontend:** abra o `index.html` no navegador

---

## Estrutura do projeto

```
helpdesk-tms-ia/
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/TicketController.php
│   │   ├── Models/Ticket.php
│   │   └── Services/OpenAIService.php      ← núcleo da triagem
│   ├── database/migrations/
│   ├── routes/api.php
│   └── .env.example
├── frontend/
│   └── index.html
└── README.md
```

---

## Decisões técnicas

- **SQLite em vez de MySQL/Postgres** — zero config para demonstração. Migração trivial trocando o driver no `.env`.
- **HTML/CSS/JS sem framework no frontend** — sem build step, sem `node_modules`, roda em qualquer lugar.
- **Prompt engineering com saída JSON estruturada** — o `OpenAIService` força o modelo a retornar JSON válido, evitando parsing frágil.
- **Triagem idempotente** — o endpoint `/triage` pode ser chamado quantas vezes quiser sem duplicar registros.

---

## Próximos passos

- [ ] Autenticação com Laravel Sanctum
- [ ] Webhook para notificar N2 em tickets de prioridade ALTA
- [ ] Dashboard com métricas de SLA por categoria
- [ ] Fila (Redis) para triagens assíncronas em alto volume

---

## Autor

**Arthur Marques**
[LinkedIn](https://linkedin.com/in/seu-usuario) · [GitHub](https://github.com/arthurmarques)

## Licença

MIT
