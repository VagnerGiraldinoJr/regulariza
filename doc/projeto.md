# Projeto Regulariza - Estado Atual

## 1) Resumo

Plataforma Laravel para operacao comercial e atendimento da CPF Clean Brasil, com:

- Landing publica (`/`)
- Funil de regularizacao (`/regularizacao`)
- Checkout e cobranca via Asaas
- Portal do cliente
- Paineis internos (admin, atendente, analista, vendedor)
- Contratos com entrada + parcelas
- Comissoes com solicitacao de saque (PIX)
- SAC (tickets + chat)
- Reset de senha por e-mail

## 2) Stack e infraestrutura

- PHP 8.4 + Laravel 12
- MySQL 8.4
- Redis
- Queue worker (`queue:work`)
- Vite + Tailwind
- Docker Compose (`app`, `queue`, `mysql`, `redis`)

## 3) Entregas implementadas

### Core de dominio

- Leads, pedidos, servicos, SAC e logs de WhatsApp
- Sistema de indicacao (referral) com credito
- Contratos com parcelas e status de pagamento
- Comissoes por venda/pagamento de parcelas
- Configuracoes de sistema em tabela (`system_settings`)

### Operacao

- Dashboard e listagens para admin/atendente
- Dashboard e carteira para analista/vendedor
- Portal do cliente com timeline, contratos, perfil e tickets
- Rotas de gestao: usuarios, vendedores, clientes, pagamentos, comissoes e integracoes

### UX/UI

- Layout premium unificado por perfil
- Sidebar recolhivel
- Fundo institucional unico (`premium-lines.jpg`)
- Transicao suave global entre paginas
- Paginas de erro customizadas (400, 401, 403, 404, 405, 419, 422, 429, 500, 503)

## 4) Rotas principais

### Publico

- `GET /`
- `GET /regularizacao`
- `GET /regularizacao/sucesso`
- `GET /regularizacao/cancelado`
- `POST /contato/whatsapp`

### Autenticacao e senha

- `GET /login`
- `POST /login`
- `POST /logout`
- `GET /esqueci-senha`
- `POST /esqueci-senha`
- `GET /resetar-senha/{token}`
- `POST /resetar-senha`

### Cliente

- `GET /portal/dashboard`
- `GET /portal/contracts`
- `GET /portal/timeline`
- `GET/POST /portal/analista/chat`
- `GET/POST /portal/tickets`

### Backoffice

- `GET /admin/orders`
- `GET /admin/contracts`
- `POST /admin/contracts`
- `GET /admin/tickets`
- `GET /admin/financeiro`
- `GET /admin/management/*` (dashboard, commissions, contract-payments, users, vendors, clients, integrations, messages, orphan-leads)

### Analista/Vendedor

- `GET /analyst/dashboard`
- `GET /analyst/contracts`
- `GET /analyst/commissions`
- `POST /analyst/commissions/{commission}/request-payout`
- `GET /analyst/clients`

## 5) Modelo de dados (tabelas)

## Base framework

- `users`
- `password_reset_tokens`
- `sessions`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

## Dominio

### `users`
Campos relevantes adicionais:
- `role` (admin, atendente, cliente, analista, vendedor)
- `cpf_cnpj`, `whatsapp`
- `avatar_path`
- `pix_key`, `pix_key_type`, `pix_holder_name`, `pix_holder_document`
- `referral_code`, `referred_by_user_id`, `referral_credits`
- `portal_token`, `portal_token_expires_at`
- `deleted_at` (soft delete)

### `services`
- `nome`, `slug`, `descricao`, `icone`, `preco`, `ativo`

### `leads`
- `cpf_cnpj`, `tipo_documento`
- `nome`, `email`, `whatsapp`
- `service_id`
- `etapa`
- `session_id`
- `referred_by_user_id`

### `orders`
- `protocolo`
- `user_id`, `service_id`, `lead_id`
- `status`
- `valor`
- `payment_provider`
- `asaas_customer_id`, `asaas_payment_id`, `payment_link_url`
- `pagamento_status`, `pago_em`
- `referral_credit_amount`, `referral_credited_at`
- `deleted_at` (soft delete)

### `contracts`
- `order_id` (unico), `user_id`, `analyst_id`
- `debt_amount`, `fee_amount`
- `entry_percentage`, `entry_amount`
- `installments_count`
- `status`
- `payment_provider`, `asaas_customer_id`
- `document_path`
- `accepted_at`, `portal_access_sent_at`, `completed_at`

### `contract_installments`
- `contract_id`, `order_id`
- `installment_number`, `label`
- `amount`, `due_date`
- `billing_type`, `payment_provider`
- `asaas_payment_id`, `payment_link_url`
- `status`, `paid_at`

### `seller_commissions`
- `order_id`, `seller_id`
- `source_type`, `source_id`
- `base_amount`, `rate`, `commission_amount`
- `status` (pending, available, paid, canceled)
- `available_at`, `payout_requested_at`, `paid_at`
- `asaas_transfer_id`, `notes`

### `sac_tickets`
- `protocolo`
- `order_id`, `user_id`, `atendente_id`
- `assunto`
- `status`
- `prioridade`
- `resolvido_em`
- `deleted_at` (soft delete)

### `sac_messages`
- `sac_ticket_id`, `user_id`
- `mensagem`, `tipo`, `arquivo_url`, `lida`

### `whatsapp_logs`
- `user_id`, `order_id`
- `telefone`
- `evento`
- `mensagem`
- `status`
- `zapi_response`
- `enviado_em`

### `system_settings`
- `key` (unico)
- `value`

## 6) Indices importantes

- `leads`: `idx_leads_documento`, `idx_leads_session_id`
- `orders`: `idx_orders_status_pagamento`, `idx_orders_pago_em`, `idx_orders_asaas_payment_id`
- `sac_tickets`: `idx_sac_tickets_status_atendente`, `idx_sac_tickets_prioridade`
- `sac_messages`: `idx_sac_messages_ticket_created`
- `contracts`: `uq_contracts_order_id`, `idx_contracts_analyst_status`
- `contract_installments`: `uq_contract_installment_number`, `idx_contract_installment_status_due`, `idx_contract_installment_asaas_payment`
- `seller_commissions`: `idx_seller_commission_status_available`, `idx_seller_commission_seller_status`, `idx_seller_commission_payout_requested`, `uq_seller_commission_order_source`

## 7) Integracoes

### Asaas

- Checkout/cobranca da pesquisa
- Cobranca de parcelas de contrato
- IDs externos persistidos em `orders` e `contract_installments`

### Z-API

- Envio de mensagens de operacao
- Rastreio de envio em `whatsapp_logs`

### SMTP (Hostinger)

- Recuperacao de senha e notificacoes por e-mail
- Configuracao via `MAIL_*` no `.env`

## 8) Deploy e operacao

Comandos padrao:

```bash
docker compose up -d --build --remove-orphans
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan optimize
```

Script de deploy:

```bash
./deploy.sh
```

## 9) Pontos de atencao

- Em VPS com WordPress no mesmo dominio, o proxy precisa rotear tambem `/build*` e `/assets*` para o Laravel.
- Para e-mail funcionar em runtime, `MAIL_*` e `APP_KEY` devem existir no `environment` do `app` e `queue` no `compose.yaml`.
- Nao expor credenciais (`APP_KEY`, `MAIL_PASSWORD`) em logs ou chats.
