<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Diagnóstico Financeiro - CPF Clean Brasil</title>
    <style>
        * { box-sizing: border-box; }
        @page { margin: 20px 22px 26px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .header {
            background: #1f78b4;
            color: #fff;
            padding: 10px 12px;
            border-radius: 8px 8px 0 0;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(255,255,255,0.14);
            padding: 4px;
            margin-right: 8px;
        }
        .title {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: .02em;
            margin: 0;
            line-height: 1.1;
        }
        .subtitle {
            margin: 2px 0 0;
            color: #d6edff;
            font-size: 10px;
        }
        .protocol {
            margin-top: 6px;
            display: inline-block;
            font-size: 9px;
            color: #14486d;
            background: #d9efff;
            padding: 3px 7px;
            border-radius: 999px;
            font-weight: 700;
        }
        .section { margin-top: 12px; }
        .section-title {
            border-bottom: 1px solid #dce4ec;
            padding: 0 0 4px;
            margin: 0 0 7px;
            font-size: 15px;
            font-weight: 700;
            color: #1f5f96;
        }
        .section-title::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 16px;
            background: #1f78b4;
            margin-right: 8px;
            vertical-align: -2px;
        }
        .table { width: 100%; border-collapse: collapse; }
        .table td { padding: 6px 8px; border: 1px solid #e5ecf3; }
        .table .label { width: 185px; font-weight: 700; color: #5d7389; background: #f8fbff; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 -8px; }
        .card {
            border: 1px solid #d9e4ee;
            background: #f8fbff;
            padding: 10px;
            border-radius: 8px;
            min-height: 62px;
        }
        .card.border-red { border-top: 4px solid #dc2626; }
        .card.border-green { border-top: 4px solid #1f8f36; }
        .card.border-blue { border-top: 4px solid #1f78b4; }
        .card-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
        .card-value { margin-top: 5px; font-size: 24px; font-weight: 700; }
        .card-value.small { font-size: 19px; }
        .text-red { color: #dc2626; }
        .text-green { color: #1f8f36; }
        .text-blue { color: #1f78b4; }
        .status-ok { color: #1f8f36; font-weight: 700; text-transform: uppercase; }
        .status-err { color: #dc2626; font-weight: 700; text-transform: uppercase; }
        .occ-table { width: 100%; border-collapse: collapse; margin-top: 3px; }
        .occ-table td { border: 1px solid #e7edf4; padding: 6px 8px; }
        .occ-table tr:nth-child(odd) td { background: #f8fbff; }
        .occ-key { font-weight: 700; color: #243a53; }
        .occ-val { text-align: right; font-weight: 700; color: #1f8f36; }
        .footer {
            margin-top: 18px;
            border-top: 1px solid #dce4ec;
            padding-top: 8px;
            font-size: 9px;
            color: #64748b;
        }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { vertical-align: middle; }
        .official { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #0f172a; }
        .cert { text-align: right; opacity: .72; }
        .cert img { width: 96px; height: auto; }
    </style>
</head>
<body>
@php
    $response = is_array($consultation->response_payload) ? $consultation->response_payload : [];
    $data = is_array($response['data'] ?? null) ? $response['data'] : [];
    $nomeDaApi = trim((string) ($data['nome'] ?? ''));
    $mostrarNome = $nomeDaApi !== '' && $nomeDaApi !== '-';
    $documento = (string) ($data['documentoConsultado'] ?? $consultation->document_number);
    $score = (string) ($data['score'] ?? '-');
    $classeRisco = (string) ($data['classeRisco'] ?? '-');
    $situacao = (string) ($data['situacao'] ?? '-');
    $statusConsulta = $consultation->status === 'success' ? 'Sucesso' : 'Erro';
    $consultaEmRaw = $data['consultaRealizadaEm'] ?? ($data['dataConsulta'] ?? null);
    $consultaEm = $consultaEmRaw ? date('d/m/Y H:i:s', strtotime((string) $consultaEmRaw)) : now()->format('d/m/Y H:i:s');
    $homolog = array_key_exists('homolog', $response) ? (bool) $response['homolog'] : null;
    $reportProtocol = 'CPFBR-'.now()->format('Ymd').'-'.str_pad((string) $consultation->id, 6, '0', STR_PAD_LEFT);

    $logoPath = public_path('assets/branding/cpfclean-logo.svg');
    $logoSvg = file_exists($logoPath) ? file_get_contents($logoPath) : null;
    $logoDataUri = $logoSvg ? 'data:image/svg+xml;base64,'.base64_encode($logoSvg) : null;

    $letsPath = public_path('assets/branding/letsencrypt-logo-horizontal.svg');
    $letsSvg = file_exists($letsPath) ? file_get_contents($letsPath) : null;
    $letsDataUri = $letsSvg ? 'data:image/svg+xml;base64,'.base64_encode($letsSvg) : null;

    $ocorrencias = [
        'Pendências' => !empty($data['possuiPendencias']) ? 'COM PENDÊNCIAS' : 'NADA CONSTA',
        'Status Geral' => $data['status'] ?? 'NÃO INFORMADO',
        'Perfil' => $data['perfil'] ?? 'NÃO INFORMADO',
        'Relacionamentos' => $data['relacionamentos'] ?? 'NÃO INFORMADO',
    ];
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width:44px;">
                @if($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="CPF Clean Brasil">
                @endif
            </td>
            <td>
                <p class="title">DIAGNÓSTICO FINANCEIRO</p>
                <p class="subtitle">Relatório de Análise Financeira • CPF Clean Brasil</p>
                <span class="protocol">Protocolo do Documento: {{ $reportProtocol }}</span>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Dados Cadastrais</h2>
    <table class="table">
        <tr><td class="label">Consulta</td><td>{{ $consultation->consultation_title ?: 'SCR Bacen + Score' }}</td></tr>
        <tr><td class="label">Documento</td><td>{{ $consultation->document_type === 'cnpj' ? 'CNPJ' : 'CPF' }}: {{ $documento }}</td></tr>
        @if($mostrarNome)
            <tr><td class="label">Nome</td><td>{{ $nomeDaApi }}</td></tr>
        @endif
        <tr><td class="label">Protocolo Comercial</td><td>{{ $consultation->order?->protocolo ?: '-' }}</td></tr>
        <tr><td class="label">Data da Consulta</td><td>{{ $consultaEm }}</td></tr>
        <tr><td class="label">Ambiente</td><td>{{ $homolog === null ? '-' : ($homolog ? 'Homologação (teste)' : 'Produção (dados reais)') }}</td></tr>
        <tr><td class="label">Status Técnico</td><td class="{{ $consultation->status === 'success' ? 'status-ok' : 'status-err' }}">{{ $statusConsulta }} (HTTP {{ $consultation->http_status ?: '-' }})</td></tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Indicadores Financeiros</h2>
    <table class="cards">
        <tr>
            <td>
                <div class="card border-blue">
                    <div class="card-label">Score</div>
                    <div class="card-value text-blue">{{ $score }}</div>
                </div>
            </td>
            <td>
                <div class="card border-green">
                    <div class="card-label">Classe de Risco</div>
                    <div class="card-value small text-green">{{ $classeRisco }}</div>
                </div>
            </td>
            <td>
                <div class="card border-red">
                    <div class="card-label">Situação</div>
                    <div class="card-value small text-red">{{ $situacao }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Resumo de Ocorrências</h2>
    <table class="occ-table">
        @foreach($ocorrencias as $key => $value)
            <tr>
                <td class="occ-key">{{ $key }}</td>
                <td class="occ-val">{{ $value }}</td>
            </tr>
        @endforeach
    </table>
</div>

<div class="footer">
    <table class="footer-table">
        <tr>
            <td>
                <div class="official">Documento Oficial CPF CLEAN BR</div>
                <div>CPF Clean Brasil • CNPJ 44.156.681/0001-57</div>
                <div>Protocolo {{ $reportProtocol }} • Emitido em {{ now()->format('d/m/Y H:i:s') }}</div>
            </td>
            <td class="cert">
                @if($letsDataUri)
                    <img src="{{ $letsDataUri }}" alt="Let's Encrypt">
                @endif
            </td>
        </tr>
    </table>
</div>
</body>
</html>
