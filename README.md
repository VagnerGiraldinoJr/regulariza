# Projeto Regulariza

Plataforma web para captacao, venda e acompanhamento de servicos de regularizacao, com fluxo guiado para o cliente, checkout online, notificacoes e operacao interna de SAC/Admin.

## Proposito do projeto

O objetivo do Regulariza e concentrar em um unico sistema:

- Entrada de leads e solicitacoes de regularizacao
- Jornada de compra com wizard em etapas
- Pagamento online e atualizacao de pedidos
- Comunicacao automatizada com cliente por WhatsApp
- Atendimento SAC com historico de mensagens
- Area administrativa para operacao e acompanhamento

## O que ja foi implementado

### Core de negocio

- Estrutura de dados com migrations para `users`, `services`, `leads`, `orders`, `sac_tickets`, `sac_messages` e `whatsapp_logs`
- Models e relacionamentos principais
- Scopes de negocio para pedidos e tickets
- Geracao automatica de protocolo no `OrderObserver` no formato `REG-YYYYMMDD-NNNNN`

### Jornada do cliente

- Componente Livewire `RegularizacaoWizard` com 4 etapas
- Mascara e validacao de CPF/CNPJ
- Selecionador de servicos com feedback visual
- Barra de progresso do fluxo

### Pagamentos e pos-pagamento

- Integracao Stripe via `StripeCheckoutService`
- Webhook com tratamento de:
- `checkout.session.completed`
- `payment_intent.payment_failed`
- Jobs assincronos apos pagamento:
- `CriarUsuarioPortal`
- `EnviarBoasVindasWhatsApp`
- `NotificarEquipeInterna`

### Comunicacao e SAC

- Integracao `ZApiService` para envio de WhatsApp com log automatico
- Templates centralizados em `config/zapi.php`
- Portal do cliente com tickets e chat em tempo real (polling de 3s)
- Painel admin/atendente para tickets e atribuicao

### Seguranca e padroes

- Middleware de papel (`role`)
- Policies para `Order` e `SacTicket`
- Form Requests para validacoes de entrada
- API Resources para padronizacao de resposta
- Horizon restrito a perfil admin

## Rotas principais

- Publico:
- `/regularizacao`
- Cliente autenticado:
- `/portal/dashboard`
- `/portal/tickets`
- `/portal/tickets/{id}`
- Admin/Atendente:
- `/admin/orders`
- `/admin/tickets`
- `/admin/tickets/{id}`

## Stack tecnica

- PHP 8.2+
- Laravel 12
- Livewire 4
- MySQL
- Redis/Horizon (filas)
- Stripe (checkout e webhooks)
- Z-API (WhatsApp)
- Vite (build frontend)

## Estado atual

- Fases 1 a 6 do checklist interno concluidas
- Ambiente local validado com migracoes e seeders
- Build de frontend validado com `npm run build`
- Suite de testes atual (basica) passando

## Como rodar localmente

1. Instale dependencias:

```bash
composer install
npm install
```

2. Configure ambiente:

```bash
cp .env.example .env
php artisan key:generate
```

3. Suba banco/infra (se usar Sail):

```bash
./vendor/bin/sail up -d
```

4. Rode migracoes e seeders:

```bash
php artisan migrate:fresh --seed
```

5. Inicie app e assets:

```bash
composer run dev
```

## Proximos passos sugeridos

- Ampliar cobertura de testes (Feature e integracao de pagamentos/webhooks)
- Definir pipeline de deploy automatizado para VPS
- Criar monitoramento operacional (fila, falhas de webhook e alertas)
