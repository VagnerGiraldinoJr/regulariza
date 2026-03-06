<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Consulta - CPF Clean Brasil</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; margin: 0; }
        .page { padding: 24px; }
        .header { background: #06253a; color: #e2f8ff; border-radius: 12px; padding: 18px 18px 16px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .logo-wrap { width: 72px; height: 72px; border-radius: 10px; background: rgba(255,255,255,.08); padding: 8px; }
        .brand { font-size: 11px; letter-spacing: 0.22em; text-transform: uppercase; color: #8de8ff; margin-bottom: 4px; }
        .title { font-size: 20px; font-weight: 700; margin: 0 0 6px; color: #ffffff; }
        .subtitle { font-size: 11px; color: #b8d9e8; margin: 0; }
        .section { margin-top: 14px; border: 1px solid #d7e1ea; border-radius: 10px; overflow: hidden; }
        .section-title { background: #f3f8fc; color: #1e3550; font-size: 11px; text-transform: uppercase; letter-spacing: .09em; font-weight: 700; padding: 8px 10px; }
        .section-body { padding: 10px; }
        .summary { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .summary td { padding: 7px 8px; border: 1px solid #e4ebf2; font-size: 11px; }
        .summary .label { width: 180px; color: #5b6f83; background: #f8fbfe; font-weight: 700; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 10px; margin: 0 -10px; }
        .metric { border: 1px solid #dbe7f2; border-radius: 10px; padding: 10px; background: #f9fcff; }
        .metric-label { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #60809b; font-weight: 700; margin-bottom: 4px; }
        .metric-value { font-size: 16px; font-weight: 700; color: #102a43; }
        .muted { color: #6b7280; font-size: 10px; }
        .ok { color: #0f766e; font-weight: 700; }
        .err { color: #b42318; font-weight: 700; }
        .footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #64748b; }
        .appendix-title { margin-top: 16px; font-size: 12px; font-weight: 700; color: #1e3550; }
        pre { white-space: pre-wrap; word-break: break-word; background: #0b1e2d; color: #d5f4ff; padding: 10px; border-radius: 8px; font-size: 10px; line-height: 1.35; }
        .logo-wrap svg { width: 100%; height: 100%; }
    </style>
</head>
<body>
    @php
        $response = is_array($consultation->response_payload) ? $consultation->response_payload : [];
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $logoPath = public_path('assets/branding/cpfclean-logo.svg');
        $logoSvg = file_exists($logoPath) ? file_get_contents($logoPath) : null;

        $score = $data['score'] ?? '-';
        $riskClass = $data['classeRisco'] ?? '-';
        $statusText = $data['status'] ?? ($response['message'] ?? '-');
        $nome = $data['nome'] ?? ($consultation->user?->name ?: '-');
        $documento = $data['documentoConsultado'] ?? $consultation->document_number;
        $consultaEm = $data['consultaRealizadaEm'] ?? ($data['dataConsulta'] ?? null);
        $consultaEm = $consultaEm ? date('d/m/Y H:i:s', strtotime((string) $consultaEm)) : '-';
        $homolog = array_key_exists('homolog', $response) ? (bool) $response['homolog'] : null;
        $valorConsulta = $response['valor_consulta'] ?? ($response['tax'] ?? '-');
    @endphp

    <div class="page">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td style="width:90px;">
                        <div class="logo-wrap">
                            @if($logoSvg)
                                {!! $logoSvg !!}
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="brand">CPF Clean Brasil</div>
                        <h1 class="title">Relatório de Consulta Financeira</h1>
                        <p class="subtitle">Documento técnico-operacional para avaliação de regularização e proposta de contrato.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Resumo Executivo</div>
            <div class="section-body">
                <table class="grid">
                    <tr>
                        <td>
                            <div class="metric">
                                <div class="metric-label">Score</div>
                                <div class="metric-value">{{ $score }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="metric">
                                <div class="metric-label">Classe de Risco</div>
                                <div class="metric-value">{{ $riskClass }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="metric">
                                <div class="metric-label">Status da Consulta</div>
                                <div class="metric-value {{ $consultation->status === 'success' ? 'ok' : 'err' }}">
                                    {{ $consultation->status === 'success' ? 'Sucesso' : 'Erro' }}
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
                <p style="margin: 2px 0 0; font-size: 11px; color:#475569;">{{ $statusText }}</p>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Dados da Solicitação</div>
            <div class="section-body">
                <table class="summary">
                    <tr><td class="label">Consulta</td><td>{{ $consultation->consultation_title ?: 'API Brasil' }}</td></tr>
                    <tr><td class="label">Categoria</td><td>{{ $consultation->consultation_category ?: '-' }}</td></tr>
                    <tr><td class="label">Cliente</td><td>{{ $nome }}</td></tr>
                    <tr><td class="label">Documento</td><td>{{ $consultation->document_type === 'cnpj' ? 'CNPJ' : 'CPF' }}: {{ $documento }}</td></tr>
                    <tr><td class="label">Protocolo</td><td>{{ $consultation->order?->protocolo ?: '-' }}</td></tr>
                    <tr><td class="label">Analista Responsável</td><td>{{ $consultation->analyst?->name ?: '-' }}</td></tr>
                    <tr><td class="label">Data da Consulta</td><td>{{ $consultaEm }}</td></tr>
                    <tr><td class="label">Ambiente</td><td>{{ $homolog === null ? '-' : ($homolog ? 'Homologação (teste)' : 'Produção (dados reais)') }}</td></tr>
                    <tr><td class="label">Tarifa da Consulta</td><td>{{ is_numeric($valorConsulta) ? 'R$ '.number_format((float) $valorConsulta, 2, ',', '.') : $valorConsulta }}</td></tr>
                    <tr><td class="label">HTTP</td><td>{{ $consultation->http_status ?: '-' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Indicadores Financeiros Relevantes</div>
            <div class="section-body">
                <table class="summary">
                    <tr><td class="label">Perfil</td><td>{{ $data['perfil'] ?? '-' }}</td></tr>
                    <tr><td class="label">Volume</td><td>{{ $data['volume'] ?? '-' }}</td></tr>
                    <tr><td class="label">Situação</td><td>{{ $data['situacao'] ?? '-' }}</td></tr>
                    <tr><td class="label">Relacionamentos</td><td>{{ $data['relacionamentos'] ?? '-' }}</td></tr>
                    <tr><td class="label">Quantidade de Operações</td><td>{{ $data['quantidadeOperacoes'] ?? '-' }}</td></tr>
                    <tr><td class="label">Quantidade de Instituições</td><td>{{ $data['quantidadeInstituicoes'] ?? '-' }}</td></tr>
                    <tr><td class="label">Possui Pendências</td><td>{{ !empty($data['possuiPendencias']) ? 'Sim' : 'Não' }}</td></tr>
                    <tr><td class="label">Total de Pendências</td><td>{{ $data['totalPendencias'] ?? '-' }}</td></tr>
                </table>
            </div>
        </div>

        @if($consultation->error_message)
            <div class="section">
                <div class="section-title">Observação de Integração</div>
                <div class="section-body" style="color:#991b1b;">{{ $consultation->error_message }}</div>
            </div>
        @endif

        <div class="appendix-title">Anexo Técnico - Retorno Bruto da API</div>
        <pre>{{ json_encode($consultation->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

        <div class="footer">
            CPF Clean Brasil • Relatório interno para apoio comercial e formalização de contrato.<br>
            Gerado em {{ now()->format('d/m/Y H:i:s') }}.
        </div>
    </div>
</body>
</html>
