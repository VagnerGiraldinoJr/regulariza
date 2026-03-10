# Onboarding do Sistema Regulariza

Data de referencia: 2026-03-10

## Objetivo

Este documento orienta a entrada da empresa no sistema `Regulariza`, cobrindo:

- acessos
- configuracao inicial
- papeis internos
- fluxo operacional ponta a ponta
- rotina diaria
- checklist de entrada em producao

## O que o sistema cobre hoje

- funil publico em `/regularizacao`
- autenticacao e recuperacao de senha
- painel administrativo em `/admin/*`
- gestao operacional em `/admin/management/*`
- painel de analista/vendedor em `/analyst/*`
- portal do cliente em `/portal/*`
- dossie PF/PJ com historico e PDF
- contratos com aceite eletronico
- pagamentos via Asaas
- SAC com tickets
- comissoes e saque PIX

## Perfis de acesso

### `admin`

- opera pedidos, contratos, financeiro e SAC
- configura integracoes
- cadastra usuarios internos e clientes
- gera dossies PF/PJ

### `atendente`

- apoia pedidos e SAC
- atua em operacao administrativa

### `analista`

- acompanha carteira
- acompanha contratos e clientes vinculados
- atende cliente por ticket/chat
- acompanha comissoes

### `vendedor`

- acompanha carteira comercial
- usa link de indicacao
- acompanha comissoes

### `cliente`

- acessa portal apos pagamento da entrada
- acompanha contrato, timeline e tickets

## Pre-requisitos antes do onboarding

Validar antes de treinar o time:

- aplicacao publicada
- banco migrado
- fila `queue` rodando
- `APP_KEY` estavel
- pelo menos 1 admin
- pelo menos 1 analista ou vendedor
- servicos PF/PJ cadastrados

## Configuracao inicial obrigatoria

### 1. Primeiro acesso

- entrar em `/login`
- confirmar redirecionamento para `/admin/orders`

### 2. Integracoes

Acesse `/admin/management/integrations` e configure:

- Asaas: `base_url`, `api_key`, `webhook_token`
- API Brasil: `base_url`, `token`, `token_header`, `token_prefix`, paths e methods
- Z-API: `instance`, `token`, `client_token`, numero WhatsApp padrao

Pontos importantes:

- os segredos aparecem mascarados
- o webhook do Asaas ja aparece pronto na tela
- a API Brasil precisa estar valida para liberar o dossie

### 3. Estrutura interna

Validar:

- usuarios em `/admin/management/users`
- analistas/vendedores em `/admin/management/vendors`
- clientes em `/admin/management/clients`
- leads sem carteira em `/admin/management/orphan-leads`

## Fluxo operacional ponta a ponta

### 1. Entrada do lead

O lead entra por `/regularizacao`.

O sistema:

- coleta dados
- identifica PF ou PJ
- cria pedido
- gera cobranca Asaas

Metodos suportados:

- PIX
- boleto
- cartao de credito

### 2. Pagamento da pesquisa

Quando o pagamento confirma:

- o pedido muda para pago
- o status operacional vai para `em_andamento`
- a comissao da pesquisa e registrada

### 3. Dossie PF/PJ

O admin acessa `/admin/management/apibrasil-consultations`.

Fluxo:

1. selecionar `PF` ou `PJ`
2. vincular pedido pago, se houver
3. informar CPF/CNPJ
4. gerar dossie

Saida esperada:

- relatorio persistido
- fontes/provedores registrados
- PDF reemitivel
- possibilidade de encaminhar para analista

### 4. Encaminhamento para analista

Na tela de consultas:

- encaminhar o dossie para analista ou vendedor responsavel

### 5. Criacao do contrato

O admin acessa `/admin/contracts`.

Na criacao, o sistema registra:

- valor da divida
- honorarios
- entrada
- documento-base opcional
- token de aceite
- expiracao do link

### 6. Aceite do contrato

O cliente recebe link individual.

No aceite, o sistema grava:

- nome informado
- IP
- user agent
- data/hora
- `accepted_hash`
- PDF final de evidencia

Regra operacional:

- a cobranca do contrato so nasce apos o aceite

### 7. Pagamento da entrada

Depois do aceite:

- a cobranca da entrada e liberada
- o pagamento ativa o contrato
- o portal do cliente e liberado

### 8. Portal do cliente

Recursos principais em `/portal/*`:

- dashboard
- contratos
- timeline
- tickets
- chat com analista

### 9. SAC

O cliente abre ticket no portal.

O time interno acompanha em `/admin/tickets`.

Fluxo padrao:

- ticket entra `aberto`
- admin/atendente assume
- status muda para `em_atendimento`

### 10. Comissoes e saques

Acompanhamento em:

- `/admin/management/commissions`
- `/admin/management/payout-requests`

O sistema cobre:

- comissao da pesquisa
- comissao das parcelas
- saque PIX solicitado pelo time comercial

## Rotina diaria recomendada

### Administrativo

- revisar pedidos em `/admin/orders`
- revisar contratos em `/admin/contracts`
- revisar tickets sem atendente em `/admin/tickets`
- revisar integracoes se houver falha operacional

### Comercial e analise

- acompanhar carteira em `/analyst/dashboard`
- revisar dossies encaminhados
- revisar contratos aguardando aceite
- acompanhar clientes em regularizacao

### Financeiro

- acompanhar `/admin/financeiro`
- validar pagamentos de pesquisa
- validar entrada e parcelas
- acompanhar comissoes e pedidos de saque

## Regras importantes

- o portal do cliente so abre apos pagamento da entrada
- o login possui limite de tentativas
- `app` e `queue` precisam compartilhar a mesma `APP_KEY`
- a fila precisa estar ativa para notificacoes e acesso ao portal
- credenciais de integracao nao devem ser expostas fora da equipe responsavel

## Checklist de entrada em producao

Antes de operar com clientes reais, validar:

- Asaas salvo e webhook funcional
- API Brasil salva e consultando
- Z-API salva
- dossie PF gerado com sucesso
- dossie PJ gerado com sucesso
- contrato criado com sucesso
- cliente consegue aceitar contrato
- pagamento da entrada libera portal
- cliente consegue abrir ticket
- financeiro consegue acompanhar comissoes

## Checklist de treinamento

Treinar o time em 5 blocos:

1. login, perfis e navegacao
2. funil `/regularizacao`
3. dossie PF/PJ
4. contratos e aceite
5. portal, SAC e comissoes

## Resultado esperado do onboarding

Ao final, a empresa deve conseguir:

- receber lead
- cobrar pesquisa
- gerar dossie PF/PJ
- encaminhar para analista
- criar contrato
- colher aceite do cliente
- cobrar entrada e parcelas
- liberar portal
- operar SAC
- acompanhar comissoes
