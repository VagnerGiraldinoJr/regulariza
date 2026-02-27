# Projeto Regulariza - Checklist

## Fase 1
- [x] Migrations de `users` (campos extras + soft deletes)
- [x] Migration `services`
- [x] Migration `leads`
- [x] Migration `orders` (+ soft deletes)
- [x] Migration `sac_tickets` (+ soft deletes)
- [x] Migration `sac_messages`
- [x] Migration `whatsapp_logs`
- [x] Models com relacionamentos
- [x] Scopes obrigatórios (`Order::pendentes/pagos`, `SacTicket::semAtendente/abertos`)
- [x] Observer de protocolo `OrderObserver` (REG-YYYYMMDD-NNNNN)

## Fase 2
- [x] Componente Livewire `RegularizacaoWizard` (4 etapas)
- [x] Máscara dinâmica CPF/CNPJ com AlpineJS
- [x] Validação de CPF/CNPJ no componente
- [x] Cards de serviço responsivos com estado selecionado
- [x] Barra de progresso visual

## Fase 3
- [x] `StripeCheckoutService` com criação de sessão e `Order` pendente
- [x] `StripeWebhookController` (`checkout.session.completed` e `payment_intent.payment_failed`)
- [x] Jobs pós-pagamento (`CriarUsuarioPortal`, `EnviarBoasVindasWhatsApp`, `NotificarEquipeInterna`)

## Fase 4
- [x] `ZApiService` com envio e log automático em `whatsapp_logs`
- [x] `config/zapi.php` com templates
- [x] Variáveis de ambiente adicionadas em `.env.example`

## Fase 5
- [x] Rotas portal (`/portal/dashboard`, `/portal/tickets`, `/portal/tickets/{id}`)
- [x] `TicketChat` Livewire com polling 3s

## Fase 6
- [x] Rotas admin (`/admin/orders`, `/admin/tickets`, `/admin/tickets/{id}`)
- [x] Listagem de tickets sem atendente (`scopeSemAtendente`)
- [x] Horizon protegido com `role:admin`

## Infra/Padrões
- [x] Middleware de role
- [x] Policies registradas (`Order`, `SacTicket`)
- [x] Form Requests para controllers
- [x] Resources JSON (`OrderResource`, `SacTicketResource`)
- [x] Seeders (`ServiceSeeder`, `AdminSeeder`, `DatabaseSeeder`)
- [x] Seeder adicional `SacSeeder` (usuário atendente SAC para validação)
- [x] Seeder adicional `ClienteSeeder` (usuário cliente para validação do portal)
- [x] Ambiente Sail/MySQL validado com `migrate:fresh --seed`
- [x] UX base melhorada (login, layout global, navegação por perfil e feedback de acesso)
