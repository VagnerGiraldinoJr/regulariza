# CPF Clean Brasil

Plataforma Laravel para funil de regularizacao, operacao de contratos, comissoes, SAC e paineis internos.

## Escopo atual

- Landing institucional em `/`
- Funil publico em `/regularizacao`
- Autenticacao e recuperacao de senha por e-mail
- Portal do cliente (`/portal/*`)
- Painel analista/vendedor (`/analyst/*`)
- Painel administrativo e gestao (`/admin/*` e `/admin/management/*`)
- Contratos com entrada + parcelas e acompanhamento de pagamentos
- Comissoes com solicitacao de saque (PIX)
- SAC com tickets e chat
- Integracoes com Asaas e Z-API

## Stack

- PHP 8.4
- Laravel 12
- MySQL 8.4
- Redis
- Queue worker (`queue:work`)
- Vite + Tailwind
- Docker Compose

## Estrutura de containers (compose.yaml)

- `app`: aplicacao web Laravel
- `queue`: worker de filas
- `mysql`: banco de dados
- `redis`: cache/fila

## Variaveis de ambiente importantes

Definidas em `.env` e injetadas no `compose.yaml`.

- App:
  - `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`
- Banco:
  - `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Sessao/Fila/Cache:
  - `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`
- E-mail (Hostinger SMTP):
  - `MAIL_MAILER=smtp`
  - `MAIL_SCHEME=smtps` (ou `tls` com porta `587`)
  - `MAIL_HOST=smtp.hostinger.com`
  - `MAIL_PORT=465`
  - `MAIL_USERNAME`, `MAIL_PASSWORD`
  - `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- Integracoes:
  - `ASAAS_*`
  - `ZAPI_*`

## Subindo local/VPS com Docker

```bash
docker compose up -d --build --remove-orphans
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan optimize
```

Aplicacao padrao:

- `http://localhost:8082` (ou dominio configurado no proxy)

## Deploy rapido

Existe script de deploy na raiz:

```bash
./deploy.sh
```

Fluxo do script:

- `git fetch` + `git pull --ff-only`
- `docker compose up -d --build --remove-orphans`
- espera banco ficar pronto
- `php artisan migrate --force`
- `optimize:clear` + `optimize`
- restart de `app` e `queue`

Opcional para reset completo do banco (destrutivo):

```bash
DEPLOY_FRESH_SEED=true ./deploy.sh
```

## Operacao em ambiente com WordPress no mesmo dominio

Quando WordPress e Laravel compartilham o dominio, o proxy reverso precisa rotear para o Laravel:

- `/login`, `/logout`
- `/regularizacao*`
- `/dashboard*`
- `/portal*`
- `/admin*`
- `/analyst*`
- `/perfil*`
- `/esqueci-senha`, `/resetar-senha*`
- `/contato/whatsapp`
- `/build*`, `/assets*`, `/storage*`

Sem isso, CSS/JS do Laravel podem cair no WordPress e quebrar layout.

## UX e layout

- Layout premium unificado (`resources/views/components/layouts/app.blade.php`)
- Fundo institucional unico: `public/assets/backgrounds/premium-lines.jpg`
- Transicao suave global entre paginas via `resources/js/app.js`
- Paginas de erro personalizadas em `resources/views/errors/`

## Troubleshooting rapido

- Verificar env em runtime:
  - `docker compose exec app printenv | grep -E '^MAIL_|^APP_KEY'`
- Verificar config mail em runtime:
  - `docker compose exec app php artisan tinker --execute="dump(config('mail.default')); dump(config('mail.mailers.smtp.host')); dump(config('mail.mailers.smtp.port')); dump(config('mail.mailers.smtp.scheme'));" `
- Logs:
  - `docker compose logs --tail=200 app queue`

## Observacoes

- Este repositorio e o projeto `regulariza` (na VPS coexistindo com outros sistemas).
- Evite expor `APP_KEY` e credenciais SMTP em logs/chats.
