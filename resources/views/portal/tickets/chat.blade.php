<x-layouts.app>
    <div class="space-y-4">
        <section>
            <h1 class="panel-title">Chat do Chamado</h1>
            <p class="panel-subtitle mt-1">Converse com a equipe do SAC sobre este ticket.</p>
        </section>

        <section class="panel-card p-4">
            <livewire:ticket-chat :ticket-id="$ticket->id" />
        </section>
    </div>
</x-layouts.app>
