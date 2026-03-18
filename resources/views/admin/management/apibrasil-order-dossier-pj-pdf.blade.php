<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Dossiê PJ Consolidado - CPF Clean Brasil</title>
    <style>
        * { box-sizing: border-box; }
        @page { margin: 18px 20px 24px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #102235; font-size: 11px; }
        .header { background: #0f3d59; color: #fff; padding: 12px 14px; border-radius: 10px; }
        .title { margin: 0; font-size: 21px; font-weight: 700; }
        .subtitle { margin: 4px 0 0; font-size: 10px; color: #cfeeff; }
        .protocol { margin-top: 8px; font-size: 9px; color: #d9f3ff; }
        .section { margin-top: 14px; }
        .section-title { margin: 0 0 8px; padding-bottom: 5px; border-bottom: 1px solid #d9e4ef; color: #17607e; font-size: 13px; font-weight: 700; }
        .table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table td, .table th { border: 1px solid #e3ebf3; padding: 6px 8px; vertical-align: top; }
        .table th { background: #eef7fb; color: #36536b; text-align: left; }
        .label { width: 200px; background: #f8fbfd; color: #48627a; font-weight: 700; }
        td, th { word-break: break-word; overflow-wrap: anywhere; }
        .pill { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .pill.ok { background: #e6f8ec; color: #17603a; }
        .pill.warn { background: #fff0d8; color: #8a5305; }
        .json-preview {
            margin-top: 4px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #f8fbff;
            font-size: 8.5px;
            line-height: 1.35;
            color: #334155;
            padding: 6px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .footer { margin-top: 18px; border-top: 1px solid #d9e4ef; padding-top: 8px; font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
@php
    $meta = $report['meta'] ?? [];
    $company = $report['company'] ?? [];
    $credit = $report['credit'] ?? [];
    $compliance = $report['compliance'] ?? [];
    $judicial = $report['judicial'] ?? [];
    $business = $report['business'] ?? [];
    $creditBehavior = $report['credit_behavior'] ?? [];
    $partners = is_array($report['partners'] ?? null) ? $report['partners'] : [];
    $sources = is_array($report['sources'] ?? null) ? $report['sources'] : [];
    $generatedAt = $meta['generated_at'] ?? now();
    if (is_string($generatedAt) && $generatedAt !== '') {
        $generatedAt = \Illuminate\Support\Carbon::parse($generatedAt);
    }
@endphp

<div class="header">
    <p class="title">DIAGNÓSTICO FINANCEIRO PJ</p>
    <p class="subtitle">Consolidado empresarial com rastreabilidade por fonte</p>
    <div class="protocol">
        Protocolo comercial: {{ $meta['commercial_protocol'] ?? ($order->protocolo ?: '-') }}<br>
        Documento: {{ $company['document'] ?? '-' }}<br>
        Total de fontes: {{ $meta['consultation_count'] ?? count($sources) }}<br>
        Emitido em: {{ $generatedAt instanceof \Illuminate\Support\Carbon ? $generatedAt->format('d/m/Y H:i:s') : (string) $generatedAt }}
    </div>
</div>

<div class="section">
    <h2 class="section-title">Dados Empresariais</h2>
    <table class="table">
        <tr><td class="label">Razão social / Nome</td><td>{{ $company['razao_social'] ?? '-' }}</td></tr>
        <tr><td class="label">CNPJ</td><td>{{ $company['document'] ?? '-' }}</td></tr>
        <tr><td class="label">Score</td><td>{{ $credit['score'] ?? '-' }}</td></tr>
        <tr><td class="label">Classe de risco</td><td>{{ $credit['classe_risco'] ?? '-' }}</td></tr>
        <tr><td class="label">Situação de crédito</td><td>{{ $credit['situacao'] ?? '-' }}</td></tr>
        <tr><td class="label">Instituições no SCR</td><td>{{ $credit['instituicoes'] ?? '0' }}</td></tr>
        <tr><td class="label">Operações no SCR</td><td>{{ $credit['operacoes'] ?? '0' }}</td></tr>
        <tr><td class="label">Crédito a vencer</td><td>{{ $credit['credito_a_vencer'] ?? '-' }}</td></tr>
        <tr><td class="label">Crédito vencido</td><td>{{ $credit['credito_vencido'] ?? '-' }}</td></tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Compliance</h2>
    <table class="table">
        <tr>
            <td class="label">Certidão Negativa PJ</td>
            <td>
                <span class="pill {{ ($compliance['certidao'] ?? '') === 'Regular' ? 'ok' : 'warn' }}">{{ $compliance['certidao'] ?? '-' }}</span>
                <div>{{ $compliance['certidao_detail'] ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td class="label">Protesto Nacional</td>
            <td>
                <span class="pill {{ ($compliance['protesto'] ?? '') === 'Regular' ? 'ok' : 'warn' }}">{{ $compliance['protesto'] ?? '-' }}</span>
                <div>{{ $compliance['protesto_detail'] ?? '-' }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Fontes Consultadas</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Fonte</th>
                <th>Status</th>
                <th>HTTP</th>
                <th>Consultado em</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sources as $source)
                <tr>
                    <td>{{ $source['title'] ?? ($source['key'] ?? '-') }}</td>
                    <td>
                        <span class="pill {{ ($source['status'] ?? 'error') === 'success' ? 'ok' : 'warn' }}">
                            {{ $source['status_label'] ?? (($source['status'] ?? '') === 'success' ? 'Sucesso' : 'Falha') }}
                        </span>
                    </td>
                    <td>{{ $source['http_status'] ?? '-' }}</td>
                    <td>{{ $source['consulted_at'] ?? '-' }}</td>
                    <td>
                        {{ $source['message'] ?: ($source['error_message'] ?: '-') }}
                        <div style="margin-top: 3px; color: #64748b;">{{ $source['endpoint'] ?? '-' }}</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(($business['company_name'] ?? '') !== '' || ($business['trade_name'] ?? '') !== '' || $partners !== [])
    <div class="section">
        <h2 class="section-title">Cadastro Empresarial (Basic PJ)</h2>
        <table class="table">
            <tr><td class="label">Razão social (fonte cadastral)</td><td>{{ $business['company_name'] ?? '-' }}</td></tr>
            <tr><td class="label">Nome fantasia</td><td>{{ $business['trade_name'] ?? '-' }}</td></tr>
            <tr><td class="label">Status da empresa</td><td>{{ $business['status'] ?? '-' }}</td></tr>
            <tr><td class="label">Atividade principal</td><td>{{ $business['main_activity'] ?? '-' }}</td></tr>
            <tr><td class="label">Atividade secundária</td><td>{{ $business['secondary_activity'] ?? '-' }}</td></tr>
            <tr><td class="label">Telefone</td><td>{{ $business['telefone'] ?? '-' }}</td></tr>
            <tr><td class="label">E-mail</td><td>{{ $business['email'] ?? '-' }}</td></tr>
            <tr><td class="label">Capital social</td><td>{{ $business['capital_social'] ?? '-' }}</td></tr>
        </table>

        <table class="table" style="margin-top: 8px;">
            <tr><td class="label">Consultas últimos 30 dias</td><td>{{ $creditBehavior['ultimos_30_dias'] ?? 0 }}</td></tr>
            <tr><td class="label">Consultas de 31 a 60 dias</td><td>{{ $creditBehavior['de_31_a_60_dias'] ?? 0 }}</td></tr>
            <tr><td class="label">Consultas de 61 a 90 dias</td><td>{{ $creditBehavior['de_61_a_90_dias'] ?? 0 }}</td></tr>
            <tr><td class="label">Consultas acima de 90 dias</td><td>{{ $creditBehavior['mais_90_dias'] ?? 0 }}</td></tr>
            <tr><td class="label">Cadastro positivo</td><td>{{ ($creditBehavior['status_cadastro_positivo'] ?? '') === '1' ? 'Ativo' : (($creditBehavior['status_cadastro_positivo'] ?? '') === '' ? '-' : 'Inativo') }}</td></tr>
        </table>

        @if($partners !== [])
            <table class="table" style="margin-top: 8px;">
                <thead>
                    <tr>
                        <th>Sócio</th>
                        <th>Documento</th>
                        <th>Tipo</th>
                        <th>Relação</th>
                        <th>Participação</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($partners as $partner)
                        <tr>
                            <td>{{ $partner['name'] ?? '-' }}</td>
                            <td>{{ $partner['document'] ?? '-' }}</td>
                            <td>{{ $partner['type'] ?? '-' }}</td>
                            <td>{{ $partner['relationship'] ?? '-' }}</td>
                            <td>{{ $partner['share'] ?? '-' }}</td>
                            <td>{{ $partner['status'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endif

@if (($judicial['count'] ?? 0) > 0)
    <div class="section">
        <h2 class="section-title">Ações e Processos</h2>
        <table class="table">
            <tr><td class="label">Total de processos</td><td>{{ $judicial['count'] }}</td></tr>
            <tr><td class="label">Em tramitação</td><td>{{ $judicial['active_count'] ?? 0 }}</td></tr>
            <tr><td class="label">Arquivados</td><td>{{ $judicial['archived_count'] ?? 0 }}</td></tr>
            <tr>
                <td class="label">Tribunais com ocorrências</td>
                <td>
                    @php $tribunals = is_array($judicial['tribunals'] ?? null) ? $judicial['tribunals'] : []; @endphp
                    @if($tribunals !== [])
                        @foreach($tribunals as $tribunal)
                            <div>{{ $tribunal['name'] ?? '-' }}: {{ $tribunal['count'] ?? 0 }}</div>
                        @endforeach
                    @else
                        -
                    @endif
                </td>
            </tr>
        </table>

        @php $topCases = is_array($judicial['top_cases'] ?? null) ? $judicial['top_cases'] : []; @endphp
        @if($topCases !== [])
            <table class="table" style="margin-top: 8px;">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tribunal</th>
                        <th>Classe</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topCases as $case)
                        <tr>
                            <td>{{ $case['number'] ?? '-' }}</td>
                            <td>{{ $case['tribunal'] ?? '-' }}</td>
                            <td>{{ $case['subject'] ?? '-' }}</td>
                            <td>{{ $case['status'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endif

<div class="section">
    <h2 class="section-title">Anexo Técnico Resumido</h2>
    @foreach($consultations as $item)
        @php
            $payload = json_encode($item->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $preview = \Illuminate\Support\Str::limit((string) $payload, 1300, "\n... [conteúdo truncado]");
        @endphp
        <div style="margin-bottom: 8px;">
            <strong>{{ $item->consultation_title ?: $item->consultation_key }}</strong>
            <div class="json-preview">{{ $preview }}</div>
        </div>
    @endforeach
</div>

<div class="footer">
    Documento Oficial CPF CLEAN BR • CNPJ 44.156.681/0001-57
</div>
</body>
</html>
