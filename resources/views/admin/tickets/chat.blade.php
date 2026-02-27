<x-layouts.app>
    <div class="grid gap-5 xl:grid-cols-[1.7fr_1fr]">
        <section class="panel-card p-4">
            <h1 class="panel-title">Chat SAC</h1>
            <p class="panel-subtitle mt-1">Conversa em tempo real com polling de 3 segundos.</p>
            <div class="mt-4">
                <livewire:ticket-chat :ticket-id="$ticket->id" />
            </div>
        </section>

        <aside class="panel-card p-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Dados do cliente</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div>
                    <dt class="text-slate-500">Nome</dt>
                    <dd class="font-medium text-slate-800">{{ $ticket->user?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Email</dt>
                    <dd class="font-medium text-slate-800">{{ $ticket->user?->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">WhatsApp</dt>
                    <dd class="font-medium text-slate-800">{{ $ticket->user?->whatsapp ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Pedido vinculado</dt>
                    <dd class="font-medium text-slate-800">{{ $ticket->order?->protocolo ?? 'Não vinculado' }}</dd>
                </div>
            </dl>
        </aside>
    </div>
</x-layouts.app>
