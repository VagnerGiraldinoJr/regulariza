# Projeto Regulariza

Plataforma web para captacao de leads, contratacao de consultoria CPF/CNPJ, acompanhamento de pedidos, indicacoes (afiliados) e operacao interna (Admin/SAC).

## Visao geral

O sistema centraliza:

- Entrada de leads e solicitacoes de consultoria
- Wizard de contratacao com 4 etapas
- Checkout online e atualizacao de status de pagamento
- Fluxo de indicacoes por link/codigo de vendedor
- Portal do cliente com pedidos, indicacoes e SAC
- Painel administrativo para pedidos, financeiro, vendedores e tickets

## Principais features

### 1) Wizard de consultoria (Publico)

- Rota: `/regularizacao`
- Fluxo de 4 etapas: Identificacao -> Consultoria -> Investimento -> Sucesso
- Validacao de CPF/CNPJ com mascara
- Posicionamento comercial ajustado para consultoria (nao venda de "servico avulso")
- Integracao com checkout Stripe (fallback local sem credenciais)

### 2) Indicacoes e afiliados

- Captura de indicacao por query string: `?indicacao=CODIGO`
- Vinculo de lead/cliente ao indicador (`referred_by_user_id`)
- Credito de indicacao aplicado em pedidos pagos (`ReferralService`)
- Dashboard do cliente com:
- contratos indicados
- total vendido
- validos x pendentes
- Admin com tela de vendedores e contratos indicados:
- rota: `/admin/vendedores`

### 3) Reenvio de link de pagamento (novo)

- Disponivel no portal do cliente para pedidos com `pagamento_status != pago`
- Funciona para:
- dono do pedido
- indicador daquele pedido
- Comportamento:
- dono do pedido: redireciona para checkout
- indicador: abre WhatsApp com mensagem e link para enviar ao indicado

### 4) Operacao interna

- Admin:
- pedidos (`/admin/orders`)
- vendedores (`/admin/vendedores`)
- financeiro (`/admin/financeiro`)
- tickets (`/admin/tickets`)
- SAC/Atendente:
- tickets e chat de atendimento
- Cliente:
- dashboard (`/portal/dashboard`)
- tickets (`/portal/tickets`)

## Stack tecnica

- PHP 8.2+
- Laravel 12
- Livewire 4
- MySQL
- Redis / Queue worker
- Stripe (Checkout + webhook)
- Z-API (WhatsApp)
- Vite
- Docker Compose

## Estrutura de seeders (atual)

Seeders principais:

- `ServiceSeeder`
- `UsersSeeder`
- `ProtocolsSeeder`

`DatabaseSeeder` chama esses 3.

### Usuarios demo criados

- Administrator: `admin@regulariza.br` / `Admin@123`
- Suporte: `sac@regulariza.br` / `Sac@123`
- Cliente: `cliente@regulariza.br` / `Cliente@123`
- Vendedor: `lucas.bahia@regulariza.br` / `Lucas@123`

### Dados demo de protocolos

- Vendedor Lucas: 10 contratos indicados
- 3 pendentes
- 7 pagos
- Cliente teste: 3 protocolos pagos

## Rodando local com Docker

1. Subir containers:

```bash
docker compose up -d --build --remove-orphans
```

2. Migrar e popular base:

```bash
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan db:seed --force
```

3. Limpar caches:

```bash
docker compose exec -T app php artisan optimize:clear
```

Aplicacao local: `http://localhost:8082`

## Reset completo do ambiente

Para recriar tudo do zero:

```bash
docker compose exec -T app php artisan migrate:fresh --seed --force
```

## Webhook Stripe

- Endpoint: `POST /api/stripe/webhook`
- Eventos tratados:
- `checkout.session.completed`
- `payment_intent.payment_failed`

## Observacoes de deploy

- O `entrypoint.sh` sincroniza variaveis criticas no `.env` interno do container para evitar divergencia entre CLI e requests web.
- Em deploy, sempre executar build + migrate + seed (quando necessario) + `optimize:clear`.

## Proximos passos sugeridos

- Adicionar testes de feature para:
- fluxo de indicacao
- reenvio de link de pagamento
- regras de permissao (dono x indicador)
- Criar auditoria de eventos comerciais (quem reenviou link, quando, para qual protocolo)
- Adicionar metricas de conversao por vendedor no painel admin
