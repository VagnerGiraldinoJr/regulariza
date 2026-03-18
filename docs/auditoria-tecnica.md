# Auditoria Tecnica

## Objetivo

Este documento define a rotina minima para manter o projeto auditavel em seguranca da informacao e qualidade de codigo.

## Checklist obrigatorio antes de deploy

1. Rodar `composer install --no-interaction --prefer-dist`
2. Rodar `npm ci`
3. Rodar `./vendor/bin/pint --test`
4. Rodar `./vendor/bin/phpstan analyse --memory-limit=1G`
5. Rodar `php artisan test`
6. Rodar `composer audit`
7. Rodar `npm audit --omit=dev --audit-level=moderate`
8. Rodar `npm run build`

## Itens de seguranca que devem ser validados

- `APP_DEBUG=false` em producao
- `APP_KEY` unica e valida em `app` e `queue`
- `SESSION_ENCRYPT=true`
- `SESSION_SECURE_COOKIE=true` em producao com HTTPS
- segredos fora do repositorio
- rotacao imediata de credenciais se qualquer segredo entrar em commit
- fila operacional ativa para notificacoes e reconciliacao
- logs sem CPF, CNPJ, token, senha, webhook secret ou payload sensivel desnecessario

## Achado critico ja identificado

O repositorio ja teve segredos expostos em `.env.example`. Mesmo removendo do arquivo atual, considere as credenciais anteriores comprometidas e faca rotacao de:

- SMTP
- ZAPI
- API Brasil
- Asaas

## Qualidade Laravel

Os seguintes padroes devem permanecer obrigatorios:

- validacao de entrada em controllers e form requests
- autorizacao com policy, gate ou middleware de papel
- sem uso de credenciais previsiveis em seeders
- sem dados demonstrativos em fluxos reais do cliente
- testes verdes antes de merge

## CI

O workflow em `.github/workflows/ci.yml` executa:

- secret scan
- Pint
- PHPStan / Larastan
- Composer audit
- NPM audit
- build do frontend
- suite de testes
