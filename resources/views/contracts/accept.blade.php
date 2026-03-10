<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aceite de Contrato - CPF Clean Brasil</title>
    <style>
        :root {
            --bg: #eef4f7;
            --panel: #ffffff;
            --ink: #143047;
            --muted: #587086;
            --line: #d7e3ec;
            --primary: #0f566e;
            --primary-soft: #dff1f6;
            --success: #166534;
            --success-bg: #dcfce7;
            --warning: #9a3412;
            --warning-bg: #ffedd5;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top right, rgba(15, 86, 110, 0.12), transparent 32%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.6), rgba(238, 244, 247, 0.96)),
                var(--bg);
        }
        .wrap { max-width: 960px; margin: 0 auto; padding: 32px 18px 56px; }
        .hero {
            border-radius: 24px;
            background: linear-gradient(135deg, #0d3146, #13617b);
            color: #fff;
            padding: 24px;
            box-shadow: 0 20px 50px rgba(15, 49, 70, 0.18);
        }
        .hero h1 { margin: 0; font-size: 30px; }
        .hero p { margin: 8px 0 0; color: rgba(255, 255, 255, 0.84); line-height: 1.5; }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            border-radius: 999px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .grid { display: grid; gap: 18px; margin-top: 20px; }
        .grid-main { grid-template-columns: 1.6fr 1fr; }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 14px 34px rgba(20, 48, 71, 0.07);
        }
        .panel h2 {
            margin: 0 0 14px;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary);
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .summary-card {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fbfdfe;
            padding: 14px;
        }
        .summary-card span {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            font-weight: 700;
        }
        .summary-card strong {
            display: block;
            margin-top: 8px;
            font-size: 22px;
            color: var(--ink);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 0; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { width: 190px; font-size: 12px; color: var(--muted); font-weight: 700; }
        td { font-size: 14px; }
        .installment {
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 12px 14px;
            background: #fbfdfe;
        }
        .installment + .installment { margin-top: 10px; }
        .installment-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            font-weight: 700;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .pill-warning { background: var(--warning-bg); color: var(--warning); }
        .pill-success { background: var(--success-bg); color: var(--success); }
        .notice {
            border-radius: 16px;
            padding: 14px 16px;
            font-size: 14px;
            line-height: 1.55;
        }
        .notice.success { background: var(--success-bg); color: var(--success); }
        .notice.warning { background: var(--warning-bg); color: var(--warning); }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            padding: 12px 16px;
            border: 1px solid transparent;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-secondary { background: var(--primary-soft); color: var(--primary); border-color: #b7dbe6; }
        .btn-light { background: #fff; color: var(--ink); border-color: var(--line); }
        .field { margin-bottom: 14px; }
        .field label { display: block; margin-bottom: 6px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; }
        .field input[type="text"] {
            width: 100%;
            border: 1px solid #c6d5e0;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 15px;
            color: var(--ink);
            background: #fff;
        }
        .check {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 14px;
            border-radius: 14px;
            background: #fbfdfe;
            border: 1px solid var(--line);
            margin-bottom: 16px;
        }
        .check input { margin-top: 3px; }
        .check span { font-size: 14px; line-height: 1.5; color: var(--ink); }
        .meta {
            margin-top: 14px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }
        .errors {
            margin: 16px 0 0;
            border-radius: 16px;
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 16px;
            font-size: 14px;
        }
        @media (max-width: 860px) {
            .grid-main { grid-template-columns: 1fr; }
            .summary-grid { grid-template-columns: 1fr; }
            th, td { display: block; width: 100%; }
            th { padding-bottom: 2px; border-bottom: 0; }
            td { padding-top: 0; }
        }
    </style>
</head>
<body>
@php
    $entryInstallment = $contract->installments->sortBy('installment_number')->firstWhere('installment_number', 0);
    $isAccepted = $contract->accepted_at !== null;
    $statusMap = [
        'aguardando_aceite' => ['label' => 'Aguardando aceite', 'class' => 'pill pill-warning'],
        'aguardando_entrada' => ['label' => 'Aguardando entrada', 'class' => 'pill pill-warning'],
        'ativo' => ['label' => 'Ativo', 'class' => 'pill pill-success'],
        'concluido' => ['label' => 'Concluído', 'class' => 'pill pill-success'],
        'cancelado' => ['label' => 'Cancelado', 'class' => 'pill pill-warning'],
    ];
    $statusInfo = $statusMap[$contract->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $contract->status)), 'class' => 'pill pill-warning'];
@endphp
    <div class="wrap">
        <section class="hero">
            <h1>Aceite do Contrato</h1>
            <p>Confira os termos comerciais, valide o documento-base quando existir e registre o seu aceite eletrônico para liberar o PDF final do contrato.</p>
            <div class="status">
                <span>Status</span>
                <strong>{{ $statusInfo['label'] }}</strong>
            </div>
        </section>

        @if (session('success'))
            <div class="notice success" style="margin-top: 18px;">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <section class="grid grid-main">
            <article class="panel">
                <h2>Resumo do Contrato</h2>

                <table>
                    <tr><th>Cliente</th><td>{{ $contract->user?->name ?: '-' }}</td></tr>
                    <tr><th>CPF/CNPJ</th><td>{{ $contract->user?->cpf_cnpj ?: '-' }}</td></tr>
                    <tr><th>Pedido</th><td>{{ $contract->order?->protocolo ?: '-' }}</td></tr>
                    <tr><th>Analista</th><td>{{ $contract->analyst?->name ?: '-' }}</td></tr>
                    <tr><th>Honorários</th><td>R$ {{ number_format((float) $contract->fee_amount, 2, ',', '.') }}</td></tr>
                    <tr><th>Valor de dívida</th><td>R$ {{ number_format((float) $contract->debt_amount, 2, ',', '.') }}</td></tr>
                    <tr><th>Entrada</th><td>{{ number_format((float) $contract->entry_percentage, 2, ',', '.') }}% - R$ {{ number_format((float) $contract->entry_amount, 2, ',', '.') }}</td></tr>
                    <tr><th>Parcelas</th><td>{{ $contract->installments_count }} parcela(s) após a entrada</td></tr>
                    <tr><th>Validade do aceite</th><td>{{ $contract->acceptance_expires_at?->format('d/m/Y H:i') ?: '-' }}</td></tr>
                    @if($isAccepted)
                        <tr><th>Aceito em</th><td>{{ $contract->accepted_at?->format('d/m/Y H:i:s') }}</td></tr>
                        <tr><th>Aceite registrado por</th><td>{{ $contract->accepted_name ?: '-' }}</td></tr>
                    @endif
                </table>

                <div class="actions">
                    @if($contract->document_path)
                        <a href="{{ route('contracts.accept.document', $contract->acceptance_token) }}" class="btn btn-secondary">Ver contrato-base</a>
                    @endif
                    @if($isAccepted)
                        <a href="{{ route('contracts.accept.pdf', $contract->acceptance_token) }}" class="btn btn-primary">Baixar PDF final</a>
                    @endif
                </div>

                <div class="meta">
                    Link protegido por token individual. O aceite registra nome informado, data e hora, IP de origem, agente do navegador e hash interno do termo apresentado.
                </div>
            </article>

            <aside class="panel">
                <h2>Resumo Financeiro</h2>
                <div class="summary-grid">
                    <div class="summary-card">
                        <span>Honorários</span>
                        <strong>R$ {{ number_format((float) $contract->fee_amount, 2, ',', '.') }}</strong>
                    </div>
                    <div class="summary-card">
                        <span>Entrada</span>
                        <strong>R$ {{ number_format((float) $contract->entry_amount, 2, ',', '.') }}</strong>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    @foreach($contract->installments->sortBy('installment_number') as $installment)
                        @php
                            $paid = $installment->status === 'pago';
                        @endphp
                        <div class="installment">
                            <div class="installment-top">
                                <span>{{ $installment->label }}</span>
                                <span class="{{ $paid ? 'pill pill-success' : 'pill pill-warning' }}">{{ ucfirst(str_replace('_', ' ', (string) $installment->status)) }}</span>
                            </div>
                            <div style="margin-top: 8px; font-size: 13px; color: var(--muted);">
                                R$ {{ number_format((float) $installment->amount, 2, ',', '.') }} • vencimento {{ $installment->due_date?->format('d/m/Y') }}
                            </div>
                            @if($installment->payment_link_url && $isAccepted)
                                <div style="margin-top: 8px;">
                                    <a href="{{ $installment->payment_link_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-light" style="padding: 8px 12px; font-size: 12px;">Abrir cobrança</a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </aside>
        </section>

        <section class="panel" style="margin-top: 18px;">
            <h2>Declaração de Aceite</h2>

            @if($isAccepted)
                <div class="notice success">
                    O aceite deste contrato já foi registrado. Use os botões acima para consultar o contrato-base, baixar o PDF final e acessar as cobranças liberadas.
                </div>
            @else
                <div class="notice warning">
                    Ao registrar o aceite, você confirma que leu os termos comerciais exibidos nesta página e, quando existente, o documento-base anexado pela equipe. As cobranças do contrato serão liberadas após essa confirmação.
                </div>

                <form method="POST" action="{{ route('contracts.accept.store', $contract->acceptance_token) }}" style="margin-top: 16px;">
                    @csrf
                    <div class="field">
                        <label for="accepted_name">Nome completo para o aceite</label>
                        <input
                            id="accepted_name"
                            type="text"
                            name="accepted_name"
                            value="{{ old('accepted_name', $contract->user?->name) }}"
                            required
                        >
                    </div>

                    <label class="check">
                        <input type="checkbox" name="accept_terms" value="1" required>
                        <span>Declaro que li e aceito os termos comerciais deste contrato, autorizando o registro eletrônico do meu aceite pela CPF Clean Brasil.</span>
                    </label>

                    <button type="submit" class="btn btn-primary">Registrar aceite eletrônico</button>
                </form>
            @endif

            <div class="meta">
                @if($entryInstallment)
                    A entrada do contrato é de R$ {{ number_format((float) $entryInstallment->amount, 2, ',', '.') }}.
                    @if($entryInstallment->status === 'pago')
                        O pagamento da entrada já consta como confirmado.
                    @else
                        Após o aceite, a cobrança da entrada será liberada para pagamento.
                    @endif
                @endif
            </div>
        </section>
    </div>
</body>
</html>
