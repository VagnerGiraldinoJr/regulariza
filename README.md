# Projeto CPF Clean Brasil

Plataforma para captacao de leads, pagamento da pesquisa, operacao de contratos, SAC e paineis internos (Admin, Analista e Cliente).

## Visao geral

O sistema inclui:

- Funil de regularizacao (`/regularizacao`) com checkout Asaas (Pix)
- Portal do cliente com pedidos, contratos, timeline e SAC
- Painel do analista com carteira, contratos e comissoes
- Painel admin com pedidos, contratos, financeiro, usuarios e integracoes
- Comissoes por pesquisa e por parcelas pagas
- Mensageria e notificacoes (WhatsApp e E-mail)

## Stack

- PHP 8.4 / Laravel 12
- MySQL 8.4
- Redis
- Queue worker
- Vite / Tailwind
- Docker Compose
- Asaas (pagamentos)
- Z-API (WhatsApp)

## Modulos principais

### 1) Regularizacao (publico)

- Rota: `GET /regularizacao`
- Captura de lead (com ou sem indicacao)
- Checkout Asaas para pagamento da pesquisa
- Obrigatoriedade de celular com mascara

### 2) Contratos (entrada + 3 parcelas)

- Tela admin: `GET /admin/contracts`
- Cria contrato com:
- entrada (percentual configurado)
- 3 parcelas (30/60/90 dias)
- upload de documento (`doc`, `docx`, `pdf`)
- Cobrancas no Asaas por parcela

### 3) Comissoes

- Pesquisa: percentual por `RESEARCH_COMMISSION_RATE`
- Parcelas pagas: percentual por `INSTALLMENT_COMMISSION_RATE`
- Janela de retencao: `COMMISSION_HOLD_HOURS` (padrao 24h)

### 4) Perfis e paineis

- Admin: pedidos, SAC, contratos, financeiro, usuarios e gestao
- Analista/Vendedor: dashboard, carteira, contratos, comissoes, perfil com PIX
- Cliente: dashboard, contratos, timeline, SAC, perfil

### 5) Reset de senha por e-mail

Fluxo publico:

- `GET /esqueci-senha`
- `POST /esqueci-senha`
- `GET /resetar-senha/{token}`
- `POST /resetar-senha`

Fluxo admin:

- Botao de envio de reset em:
- `GET /admin/management/users`
- `GET /admin/management/clients`
- Cadastro vendedor com envio automatico de reset:
- `GET /admin/management/vendors`
- `POST /admin/management/vendors`

### 6) Paginas de erro customizadas

Paginas no estilo institucional para:

- 400, 401, 403, 404, 405, 419, 422, 429, 500 e 503

Arquivos:

- `resources/views/errors/error.blade.php`
- `resources/views/errors/*.blade.php`

## UI/UX recentes

- Sidebar premium com recolhimento
- Cards com transparencia
- Badges em status/tipos
- Fundo institucional com motion suave
- Footer fixo com status + data/hora servidor
- Selo Site Blindado e marca LetsEncrypt no layout
- Icone Laravel discreto no footer
- Widget WhatsApp corrigido com icone customizado e validacao isolada

## E-mail (Hostinger SMTP)

Variaveis usadas:

- `MAIL_MAILER=smtp`
- `MAIL_SCHEME=smtps`
- `MAIL_HOST=smtp.hostinger.com`
- `MAIL_PORT=465`
- `MAIL_USERNAME=...`
- `MAIL_PASSWORD=...`
- `MAIL_FROM_ADDRESS=contato@cpfclean.com.br`
- `MAIL_FROM_NAME="CPF Clean Brasil"`

## Rodando local com Docker

1. Subir ambiente:

```bash
docker compose up -d --build --remove-orphans
```

2. Migrar e semear:

```bash
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan db:seed --force
```

3. Limpar cache:

```bash
docker compose exec -T app php artisan optimize:clear
```

Aplicacao local:

- `http://localhost:8082`

## Usuarios padrao

- Admin: `admin@cpfclean.com.br` / `Admin@123`
- Analista: `analista@cpfclean.com.br` / `Analista@123`
- SAC: `sac@cpfclean.com.br` / `Sac@123`
- Cliente: `cliente@cpfclean.com.br` / `Cliente@123`

## Testes

No host (com dependencias de dev):

```bash
php artisan test
```

## Observacoes de deploy

- Este projeto sobe `app` e `queue` por imagem (sem bind mount de codigo em runtime).
- Sempre que alterar views/css/controllers:

```bash
docker compose up -d --build app queue
docker compose exec app php artisan optimize:clear
```

