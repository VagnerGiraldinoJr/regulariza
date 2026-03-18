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
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .title { margin: 0; font-size: 23px; font-weight: 700; letter-spacing: .02em; }
        .subtitle { margin: 4px 0 0; font-size: 10px; color: #cfeeff; }
        .protocol { margin-top: 8px; font-size: 9px; color: #d9f3ff; }
        .section { margin-top: 14px; }
        .section-title { margin: 0 0 8px; padding-bottom: 5px; border-bottom: 1px solid #d9e4ef; color: #17607e; font-size: 13px; font-weight: 700; }
        .table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table td, .table th { border: 1px solid #e3ebf3; padding: 6px 8px; vertical-align: top; }
        .table th { background: #eef7fb; color: #36536b; text-align: left; }
        .label { width: 200px; background: #f8fbfd; color: #48627a; font-weight: 700; }
        td, th { word-break: break-word; overflow-wrap: anywhere; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 -8px; }
        .card { border: 1px solid #dce7ef; border-radius: 10px; background: #f8fbfd; padding: 10px; min-height: 72px; }
        .card-label { font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; }
        .card-value { margin-top: 6px; font-size: 24px; font-weight: 700; color: #0f3d59; }
        .card-value.small { font-size: 16px; line-height: 1.25; }
        .pill { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .pill.ok { background: #e6f8ec; color: #17603a; }
        .pill.warn { background: #fff0d8; color: #8a5305; }
        .pill.danger { background: #fee6e2; color: #8e2218; }
        .note { margin-top: 8px; padding: 8px 10px; border: 1px solid #dce7ef; border-radius: 8px; background: #f8fbfd; color: #355066; font-size: 10px; line-height: 1.45; }
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
    $rating = array_replace([
        'classification' => 'NÃO CLASSIFICADO',
        'moodys' => '-',
        'sp' => '-',
        'fitch' => '-',
    ], $credit['rating'] ?? []);
    $logoPath = public_path('assets/branding/cpfclean-logo.svg');
    $logoSvg = file_exists($logoPath) ? file_get_contents($logoPath) : null;
    $logoDataUri = $logoSvg ? 'data:image/svg+xml;base64,'.base64_encode($logoSvg) : null;
    $riskPill = match ($rating['sp']) {
        'AAA', 'AA+', 'AA', 'AA-', 'A+', 'A', 'A-' => 'pill ok',
        'BBB+', 'BBB', 'BBB-', 'BB+', 'BB', 'BB-' => 'pill warn',
        default => 'pill danger',
    };
    $generatedAt = $meta['generated_at'] ?? now();
    if (is_string($generatedAt) && $generatedAt !== '') {
        $generatedAt = \Illuminate\Support\Carbon::parse($generatedAt);
    }
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width:50px; vertical-align: top;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="CPF Clean Brasil" style="width:36px; height:36px;">
                @endif
            </td>
            <td>
                <p class="title">DIAGNÓSTICO FINANCEIRO PJ</p>
                <p class="subtitle">Consolidado empresarial no padrão executivo da análise PF</p>
                <div class="protocol">
                    Protocolo comercial: {{ $meta['commercial_protocol'] ?? ($order->protocolo ?: '-') }}<br>
                    Documento: {{ $company['document'] ?? '-' }}<br>
                    Total de fontes: {{ $meta['consultation_count'] ?? count($sources) }}<br>
                    Emitido em: {{ $generatedAt instanceof \Illuminate\Support\Carbon ? $generatedAt->format('d/m/Y H:i:s') : (string) $generatedAt }}
                </div>
            </td>
        </tr>
    </table>
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
    <h2 class="section-title">Indicadores Financeiros</h2>
    <table class="cards">
        <tr>
            <td>
                <div class="card">
                    <div class="card-label">Score Principal</div>
                    <div class="card-value">{{ $credit['score'] ?? '-' }}</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="card-label">Rating S&amp;P / Fitch</div>
                    <div class="card-value">{{ $rating['sp'] }} / {{ $rating['fitch'] }}</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="card-label">Classe de Risco API</div>
                    <div class="card-value small">{{ $credit['classe_risco'] ?? '-' }}</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="card-label">Risco Consolidado</div>
                    <div class="card-value small"><span class="{{ $riskPill }}">{{ $rating['classification'] }}</span></div>
                </div>
            </td>
        </tr>
    </table>
    <div class="note">
        <strong>Situação de crédito:</strong> {{ $credit['situacao'] ?? '-' }}<br>
        <strong>Instituições/Operações no SCR:</strong> {{ $credit['instituicoes'] ?? '0' }} / {{ $credit['operacoes'] ?? '0' }}<br>
        <strong>Crédito a vencer / vencido:</strong> {{ $credit['credito_a_vencer'] ?? '-' }} / {{ $credit['credito_vencido'] ?? '-' }}
    </div>
</div>

<div class="section">
    <h2 class="section-title">Classificação do Risco de Crédito</h2>
    <table class="table">
        <tr>
            <th>Classificação</th>
            <th>Moody's</th>
            <th>Standard &amp; Poor's</th>
            <th>Fitch</th>
        </tr>
        <tr>
            <td>{{ $rating['classification'] }}</td>
            <td>{{ $rating['moodys'] }}</td>
            <td>{{ $rating['sp'] }}</td>
            <td>{{ $rating['fitch'] }}</td>
        </tr>
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

<div class="footer">
    Documento Oficial CPF CLEAN BR • CNPJ 44.156.681/0001-57
</div>
</body>
</html>
