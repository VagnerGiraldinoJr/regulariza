<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Contrato com Aceite - CPF Clean Brasil</title>
    <style>
        * { box-sizing: border-box; }
        @page { margin: 20px 24px 28px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #123047; font-size: 11px; }
        .header {
            padding: 14px 16px;
            border-radius: 14px;
            background: #123f58;
            color: #fff;
        }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 6px 0 0; font-size: 10px; color: #dcecf4; }
        .section { margin-top: 16px; }
        .section h2 {
            margin: 0 0 8px;
            color: #145270;
            font-size: 14px;
            border-bottom: 1px solid #dbe6ee;
            padding-bottom: 5px;
        }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #dfe8ef; padding: 7px 8px; vertical-align: top; text-align: left; }
        .table th { width: 180px; background: #f5fafc; color: #466177; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 -8px; }
        .card {
            border: 1px solid #dbe6ee;
            border-radius: 12px;
            background: #f9fbfd;
            padding: 10px;
        }
        .card-label { font-size: 9px; color: #5b7388; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; }
        .card-value { margin-top: 6px; font-size: 20px; font-weight: 700; color: #123047; }
        .muted { color: #62788b; }
        .legal {
            border: 1px solid #dbe6ee;
            border-radius: 12px;
            background: #f9fbfd;
            padding: 12px;
            line-height: 1.55;
        }
        .contract-terms h2 {
            margin: 0 0 8px;
            color: #145270;
            font-size: 14px;
            border-bottom: 1px solid #dbe6ee;
            padding-bottom: 5px;
        }
        .contract-terms__intro {
            margin: 0 0 10px;
            color: #62788b;
            font-size: 10px;
        }
        .contract-terms__block,
        .contract-terms__clause {
            margin-top: 10px;
            line-height: 1.6;
        }
        .contract-terms__clause h3 {
            margin: 0 0 5px;
            color: #123047;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .contract-terms ul {
            margin: 8px 0 0 18px;
            padding: 0;
        }
        .footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #dbe6ee;
            font-size: 9px;
            color: #62788b;
        }
    </style>
</head>
<body>
@php
    $entryInstallment = $contract->installments->sortBy('installment_number')->firstWhere('installment_number', 0);
@endphp
    <div class="header">
        <h1>Contrato com Aceite Eletrônico</h1>
        <p>
            Pedido {{ $contract->order?->protocolo ?: '-' }} • Contrato #{{ $contract->id }}<br>
            Emitido em {{ now()->format('d/m/Y H:i:s') }} • CPF Clean Brasil
        </p>
    </div>

    <div class="section">
        <h2>Partes e Identificação</h2>
        <table class="table">
            <tr><th>Cliente</th><td>{{ $contract->user?->name ?: '-' }}</td></tr>
            <tr><th>CPF/CNPJ</th><td>{{ $contract->user?->cpf_cnpj ?: '-' }}</td></tr>
            <tr><th>E-mail</th><td>{{ $contract->user?->email ?: '-' }}</td></tr>
            <tr><th>WhatsApp</th><td>{{ $contract->user?->whatsapp ?: '-' }}</td></tr>
            <tr><th>Analista</th><td>{{ $contract->analyst?->name ?: '-' }}</td></tr>
            <tr><th>Status atual</th><td>{{ ucfirst(str_replace('_', ' ', (string) $contract->status)) }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Condições Comerciais</h2>
        <table class="cards">
            <tr>
                <td>
                    <div class="card">
                        <div class="card-label">Honorários</div>
                        <div class="card-value">R$ {{ number_format((float) $contract->fee_amount, 2, ',', '.') }}</div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="card-label">Entrada</div>
                        <div class="card-value">R$ {{ number_format((float) $contract->entry_amount, 2, ',', '.') }}</div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="card-label">Percentual da entrada</div>
                        <div class="card-value">{{ number_format((float) $contract->entry_percentage, 2, ',', '.') }}%</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="table" style="margin-top: 8px;">
            <tr><th>Valor da dívida informado</th><td>R$ {{ number_format((float) $contract->debt_amount, 2, ',', '.') }}</td></tr>
            <tr><th>Parcelamento previsto</th><td>{{ $contract->installments_count }} parcela(s) após a entrada</td></tr>
            <tr><th>Documento-base anexado</th><td>{{ $contract->document_path ? basename((string) $contract->document_path) : 'Não enviado' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Cronograma Financeiro</h2>
        <table class="table">
            <tr>
                <th style="width: 110px;">Parcela</th>
                <th style="width: 120px;">Vencimento</th>
                <th style="width: 120px;">Valor</th>
                <th>Status</th>
            </tr>
            @foreach($contract->installments->sortBy('installment_number') as $installment)
                <tr>
                    <td>{{ $installment->label }}</td>
                    <td>{{ $installment->due_date?->format('d/m/Y') ?: '-' }}</td>
                    <td>R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', (string) $installment->status)) }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        @include('contracts.partials.confession-terms', ['contract' => $contract])
    </div>

    <div class="section">
        <h2>Evidência do Aceite</h2>
        <table class="table">
            <tr><th>Nome informado no aceite</th><td>{{ $contract->accepted_name ?: $contract->user?->name ?: '-' }}</td></tr>
            <tr><th>Data e hora do aceite</th><td>{{ $contract->accepted_at?->format('d/m/Y H:i:s') ?: '-' }}</td></tr>
            <tr><th>IP de origem</th><td>{{ $contract->accepted_ip ?: '-' }}</td></tr>
            <tr><th>Navegador / dispositivo</th><td>{{ $contract->accepted_user_agent ?: '-' }}</td></tr>
            <tr><th>Token do aceite</th><td>{{ $contract->acceptance_token ?: '-' }}</td></tr>
            <tr><th>Hash do termo</th><td>{{ $contract->accepted_hash ?: '-' }}</td></tr>
        </table>

        <div class="legal" style="margin-top: 8px;">
            O cliente acima identificado registrou aceite eletrônico neste contrato por meio de link individualizado. O aceite cobre as condições comerciais exibidas ao cliente no momento da confirmação, incluindo honorários, entrada, parcelamento e referência ao documento-base anexado quando existente.
        </div>
    </div>

    <div class="section">
        <h2>Declaração Operacional</h2>
        <div class="legal">
            A CPF Clean Brasil registra este documento como comprovação interna do aceite do cliente, sem integração com assinatura digital qualificada nesta etapa. O fluxo operacional adotado é baseado em confirmação expressa do cliente, captura de data e hora, IP de origem, agente do navegador e geração deste PDF final para auditoria e acompanhamento comercial.
        </div>
    </div>

    <div class="footer">
        Contrato #{{ $contract->id }} • Pedido {{ $contract->order?->protocolo ?: '-' }}<br>
        @if($entryInstallment)
            Entrada prevista: R$ {{ number_format((float) $entryInstallment->amount, 2, ',', '.') }} • status {{ ucfirst(str_replace('_', ' ', (string) $entryInstallment->status)) }}<br>
        @endif
        Documento gerado automaticamente pelo sistema CPF Clean Brasil.
    </div>
</body>
</html>
