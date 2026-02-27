<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">SAC do Cliente</h1>
            <p class="panel-subtitle mt-1">Abra chamados e acompanhe o atendimento da equipe.</p>
        </section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <section class="grid gap-5 lg:grid-cols-[1fr_1.2fr]">
            <form method="POST" action="{{ route('portal.tickets.store') }}" class="panel-card p-4">
                @csrf
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Novo chamado</h2>

                <div class="mt-3 space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Assunto</label>
                        <input name="assunto" value="{{ old('assunto') }}" placeholder="Ex: Dúvida sobre documentação" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                        @error('assunto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Mensagem inicial</label>
                        <textarea name="mensagem" rows="4" placeholder="Descreva sua necessidade..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('mensagem') }}</textarea>
                        @error('mensagem')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Prioridade</label>
                        <select name="prioridade" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach (['nova', 'baixa', 'media', 'alta', 'critica'] as $prioridade)
                                <option value="{{ $prioridade }}" @selected(old('prioridade', 'nova') === $prioridade)>{{ ucfirst($prioridade) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn-primary w-full">Abrir chamado</button>
                </div>
            </form>

            <div class="panel-card overflow-hidden">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Chamados abertos</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($tickets as $ticket)
                        <a href="{{ route('portal.tickets.show', $ticket->id) }}" class="block px-4 py-3 hover:bg-slate-50">
                            <p class="text-sm font-semibold text-slate-800">{{ $ticket->protocolo }} - {{ $ticket->assunto }}</p>
                            <p class="mt-1 text-xs text-slate-500">Status: {{ $ticket->status }} | Prioridade: {{ $ticket->prioridade }}</p>
                        </a>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-500">Nenhum ticket encontrado.</div>
                    @endforelse
                </div>

                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $tickets->links() }}
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
