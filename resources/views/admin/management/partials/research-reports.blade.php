<section class="panel-card overflow-hidden">
    <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Dossiês consolidados recentes</h2>
            <p class="mt-1 text-xs text-slate-500">Cada relatório abaixo representa um pacote PF/PJ persistido, com reemissão por PDF.</p>
        </div>
        <span class="badge badge-success">{{ $reports->count() }} recentes</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">Quando</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Documento</th>
                    <th class="px-4 py-3">Pedido</th>
                    <th class="px-4 py-3">Fontes</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr class="border-t border-slate-100 align-top">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $report->generated_at?->format('d/m/Y H:i') ?: $report->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $report->title }}</div>
                            <div class="text-xs uppercase tracking-wide text-slate-500">{{ $report->report_type }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-700">{{ strtoupper($report->document_type) }}</div>
                            <div class="text-xs text-slate-500">{{ $report->document_number }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($report->order)
                                <div class="font-semibold text-slate-700">{{ $report->order->protocolo }}</div>
                                <div class="text-xs text-slate-500">{{ $report->user?->name ?: 'Cliente não encontrado' }}</div>
                            @else
                                <span class="text-xs text-slate-500">Geração manual</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-xs text-slate-600">{{ $report->success_count }}/{{ $report->source_count }} sucesso</div>
                            @if ($report->failure_count > 0)
                                <div class="text-xs text-red-700">{{ $report->failure_count }} falha(s)</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{
                                $report->status === 'success'
                                    ? 'badge-success'
                                    : ($report->status === 'partial' ? 'badge-warning' : 'badge-danger')
                            }}">
                                {{ $report->status === 'success' ? 'Sucesso' : ($report->status === 'partial' ? 'Parcial' : 'Erro') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.management.apibrasil-consultations.reports.pdf', $report) }}" data-no-transition class="inline-flex rounded-md bg-slate-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                Baixar PDF
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">Nenhum dossiê consolidado gerado ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
