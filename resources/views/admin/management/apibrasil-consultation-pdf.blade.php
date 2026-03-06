<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Consulta - CPF Clean Brasil</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 6px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .muted { color: #6b7280; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
        .ok { background: #dcfce7; color: #166534; }
        .err { background: #fee2e2; color: #991b1b; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
        pre { white-space: pre-wrap; word-break: break-word; background: #0f172a; color: #d1fae5; padding: 10px; border-radius: 6px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 0; vertical-align: top; }
        .label { width: 180px; color: #6b7280; }
    </style>
</head>
<body>
    <h1>CPF Clean Brasil - Relatório de Consulta</h1>
    <p class="muted">Documento gerado em {{ now()->format('d/m/Y H:i:s') }}</p>

    <div class="box">
        <table>
            <tr><td class="label">Consulta</td><td>{{ $consultation->consultation_title ?: 'API Brasil' }}</td></tr>
            <tr><td class="label">Categoria</td><td>{{ $consultation->consultation_category ?: '-' }}</td></tr>
            <tr><td class="label">Documento</td><td>{{ $consultation->document_type === 'cnpj' ? 'CNPJ' : 'CPF' }}: {{ $consultation->document_number }}</td></tr>
            <tr><td class="label">Protocolo</td><td>{{ $consultation->order?->protocolo ?: '-' }}</td></tr>
            <tr><td class="label">Cliente</td><td>{{ $consultation->user?->name ?: '-' }}</td></tr>
            <tr><td class="label">Analista</td><td>{{ $consultation->analyst?->name ?: '-' }}</td></tr>
            <tr>
                <td class="label">Status</td>
                <td>
                    <span class="badge {{ $consultation->status === 'success' ? 'ok' : 'err' }}">
                        {{ $consultation->status === 'success' ? 'SUCESSO' : 'ERRO' }}
                    </span>
                    @if($consultation->http_status)
                        <span class="muted">HTTP {{ $consultation->http_status }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @if($consultation->error_message)
        <h2>Erro da integração</h2>
        <div class="box">{{ $consultation->error_message }}</div>
    @endif

    <h2>Retorno bruto da API</h2>
    <pre>{{ json_encode($consultation->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</body>
</html>
