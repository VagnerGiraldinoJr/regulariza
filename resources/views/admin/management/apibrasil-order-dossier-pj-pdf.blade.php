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
        .label { width: 220px; background: #f8fbfd; color: #48627a; font-weight: 700; }
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
        .rating-board { width: 100%; border-collapse: separate; border-spacing: 10px; }
        .rating-panel { border: 1px solid #dce7ef; border-radius: 10px; background: #f8fbfd; padding: 10px; }
        .rating-panel-title { font-size: 10px; font-weight: 700; color: #36536b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
        .rating-current { font-size: 64px; line-height: 1; font-weight: 700; color: #8a5305; text-align: center; margin-top: 8px; }
        .rating-scale-table { width: 100%; border-collapse: collapse; }
        .rating-scale-table td { border: 0; padding: 2px 0; font-size: 11px; }
        .rating-dot { display: inline-block; width: 14px; height: 12px; border-radius: 2px; margin-right: 6px; }
        .rating-code { font-weight: 700; color: #243b53; }
        .rating-current-row { background: #f8ecd8; }
        .rating-hint { margin-top: 8px; font-size: 9px; color: #64748b; line-height: 1.35; }
    </style>
</head>
<body>
@php
    $meta = $report['meta'] ?? [];
    $company = $report['company'] ?? [];
    $credit = $report['credit'] ?? [];
    $compliance = $report['compliance'] ?? [];
    $complianceEntries = is_array($report['compliance_entries'] ?? null) ? $report['compliance_entries'] : [];
    $judicial = $report['judicial'] ?? [];
    $business = $report['business'] ?? [];
    $creditBehavior = $report['credit_behavior'] ?? [];
    $contacts = $report['contacts'] ?? [];
    $publicDebts = is_array($report['public_debts'] ?? null) ? $report['public_debts'] : [];
    $negatives = $report['negatives'] ?? [];
    $registration = $report['registration'] ?? [];
    $consultationHistory = $report['consultation_history'] ?? [];
    $patrimony = $report['patrimony'] ?? [];
    $partners = is_array($report['partners'] ?? null) ? $report['partners'] : [];
    $businessIndicators = is_array($report['business_indicators'] ?? null) ? $report['business_indicators'] : [];
    $sources = is_array($report['sources'] ?? null) ? $report['sources'] : [];
    $sources = collect($sources)
        ->filter(fn ($source) => is_array($source))
        ->reject(fn ($source) => (string) ($source['key'] ?? '') === 'scr_bacen_score_pj')
        ->values()
        ->all();
    $displayedSourceCount = count($sources);
    $hasComplianceEntries = $complianceEntries !== [];
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
    $ratingScale = [
        ['code' => 'AAA', 'color' => '#1f8f3a'],
        ['code' => 'AA', 'color' => '#2d9b40'],
        ['code' => 'A', 'color' => '#3aa84a'],
        ['code' => 'BBB', 'color' => '#5aa134'],
        ['code' => 'BB', 'color' => '#7b9f2e'],
        ['code' => 'B', 'color' => '#d08a1a'],
        ['code' => 'CCC', 'color' => '#d96f16'],
        ['code' => 'CC', 'color' => '#ce4a19'],
        ['code' => 'C', 'color' => '#b8321a'],
        ['code' => 'D', 'color' => '#a1221b'],
        ['code' => 'E', 'color' => '#8c1515'],
    ];
    $ratingCurrent = strtoupper(trim((string) ($rating['sp'] ?? '')));
    $ratingCurrent = in_array($ratingCurrent, array_column($ratingScale, 'code'), true) ? $ratingCurrent : '-';
    $generatedAt = $meta['generated_at'] ?? now();
    if (is_string($generatedAt) && $generatedAt !== '') {
        $generatedAt = \Illuminate\Support\Carbon::parse($generatedAt);
    }
    $sanitizeChargeMessage = function ($value): string {
        if (! is_scalar($value)) {
            return '';
        }

        $clean = trim((string) $value);
        if ($clean === '') {
            return '';
        }

        $clean = preg_replace('/valor\s+da\s+consulta:\s*r\$\s*[\d\.,]+!?/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/voc[eê]\s+foi\s+tarifado\s+em\s+r\$\s*[\d\.,]+!?/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/\br\$\s*[\d\.,]+/iu', 'R$ [oculto]', $clean) ?? $clean;
        $clean = preg_replace('/\s{2,}/u', ' ', trim($clean)) ?? $clean;

        return $clean;
    };
    $formatDocument = function ($value): string {
        if (! is_scalar($value)) {
            return '-';
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return '-';
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === null || $digits === '') {
            return $raw;
        }

        if (strlen($digits) === 14) {
            return substr($digits, 0, 2).'.'.substr($digits, 2, 3).'.'.substr($digits, 5, 3).'/'.substr($digits, 8, 4).'-'.substr($digits, 12, 2);
        }

        if (strlen($digits) === 11) {
            return substr($digits, 0, 3).'.'.substr($digits, 3, 3).'.'.substr($digits, 6, 3).'-'.substr($digits, 9, 2);
        }

        return $raw;
    };
    $formatCurrencyBr = function ($value): string {
        if (! is_scalar($value)) {
            return '-';
        }

        $raw = trim((string) $value);
        if ($raw === '' || $raw === '-') {
            return '-';
        }

        $normalized = preg_replace('/[^\d,.\-]/', '', $raw);
        if (! is_string($normalized) || $normalized === '') {
            return $raw;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! is_numeric($normalized)) {
            return $raw;
        }

        return 'R$ '.number_format((float) $normalized, 2, ',', '.');
    };
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
                <p class="subtitle">Consolidado empresarial analítico no padrão executivo da análise PF</p>
                <div class="protocol">
                    Protocolo comercial: {{ $meta['commercial_protocol'] ?? ($order->protocolo ?: '-') }}<br>
                    Documento: {{ $formatDocument($company['document'] ?? '-') }}<br>
                    Total de fontes: {{ $displayedSourceCount }}<br>
                    Emitido em: {{ $generatedAt instanceof \Illuminate\Support\Carbon ? $generatedAt->format('d/m/Y H:i:s') : (string) $generatedAt }}
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Dados Empresariais</h2>
    <table class="table">
        <tr><td class="label">Razão social</td><td>{{ $company['razao_social'] ?? '-' }}</td></tr>
        <tr><td class="label">Nome fantasia</td><td>{{ $company['nome_fantasia'] ?? ($business['trade_name'] ?? '-') }}</td></tr>
        <tr><td class="label">CNPJ</td><td>{{ $formatDocument($company['document'] ?? '-') }}</td></tr>
        <tr><td class="label">Situação cadastral</td><td>{{ $registration['situacao_cadastral'] ?? ($business['status'] ?? '-') }}</td></tr>
        <tr><td class="label">Natureza jurídica</td><td>{{ $business['natureza_juridica'] ?? '-' }}</td></tr>
        <tr><td class="label">Data início atividade</td><td>{{ $registration['data_inicio_atividade'] ?? '-' }}</td></tr>
        <tr><td class="label">NIRE</td><td>{{ $registration['nire'] ?? '-' }}</td></tr>
        <tr><td class="label">Regime tributário</td><td>{{ $registration['regime'] ?? '-' }}</td></tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Indicadores Financeiros</h2>
    <table class="cards">
        <tr>
            <td><div class="card"><div class="card-label">Score Principal</div><div class="card-value">{{ $credit['score'] ?? '-' }}</div></div></td>
            <td><div class="card"><div class="card-label">Rating S&amp;P / Fitch</div><div class="card-value">{{ $rating['sp'] }} / {{ $rating['fitch'] }}</div></div></td>
            <td><div class="card"><div class="card-label">Classe de Risco API</div><div class="card-value small">{{ $credit['classe_risco'] ?? '-' }}</div></div></td>
            <td><div class="card"><div class="card-label">Risco Consolidado</div><div class="card-value small"><span class="{{ $riskPill }}">{{ $rating['classification'] }}</span></div></div></td>
        </tr>
    </table>
    <div class="note">
        <strong>Probabilidade:</strong> {{ $credit['probabilidade'] ?? '-' }}<br>
        <strong>Situação de crédito:</strong> {{ $credit['situacao'] ?? '-' }}<br>
        @if(($credit['has_scr'] ?? false) === true)
            <strong>Instituições/Operações no SCR:</strong> {{ $credit['instituicoes'] ?? '0' }} / {{ $credit['operacoes'] ?? '0' }}<br>
            <strong>Crédito a vencer / vencido:</strong> {{ $credit['credito_a_vencer'] ?? '-' }} / {{ $credit['credito_vencido'] ?? '-' }}
        @else
            <strong>Instituições/Operações no SCR:</strong> Não aplicável no pacote PJ atual<br>
            <strong>Crédito a vencer / vencido:</strong> Não aplicável no pacote PJ atual
        @endif
    </div>
</div>

<div class="section">
    <h2 class="section-title">Classificação do Risco de Crédito</h2>
    <table class="rating-board">
        <tr>
            <td style="width: 34%;">
                <div class="rating-panel">
                    <div class="rating-panel-title">Rating atual</div>
                    <div class="rating-current">{{ $ratingCurrent }}</div>
                </div>
            </td>
            <td style="width: 66%;">
                <div class="rating-panel">
                    <div class="rating-panel-title">Escala de classificação</div>
                    <table class="rating-scale-table">
                        @foreach($ratingScale as $scale)
                            @php $isCurrent = $scale['code'] === $ratingCurrent; @endphp
                            <tr class="{{ $isCurrent ? 'rating-current-row' : '' }}">
                                <td style="width:22px;"><span class="rating-dot" style="background: {{ $scale['color'] }}"></span></td>
                                <td class="rating-code">{{ $scale['code'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                    <div class="rating-hint">
                        A faixa de classificação auxilia na estimativa de risco com base no score consolidado.
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <table class="table">
        <tr><th>Classificação</th><th>Moody's</th><th>Standard &amp; Poor's</th><th>Fitch</th></tr>
        <tr><td>{{ $rating['classification'] }}</td><td>{{ $rating['moodys'] }}</td><td>{{ $rating['sp'] }}</td><td>{{ $rating['fitch'] }}</td></tr>
    </table>
</div>

@if($hasComplianceEntries)
    <div class="section">
        <h2 class="section-title">Compliance e Órgãos</h2>
        <table class="table">
            <tr>
                <td class="label">Consolidado compliance</td>
                <td>
                    <span class="pill {{ ($compliance['certidao'] ?? '') === 'Regular' ? 'ok' : 'warn' }}">{{ $compliance['certidao'] ?? '-' }}</span>
                    <div>{{ $compliance['certidao_detail'] ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td class="label">Observação operacional</td>
                <td>{{ $compliance['protesto_detail'] ?? '-' }}</td>
            </tr>
        </table>

        <table class="table" style="margin-top: 8px;">
            <thead><tr><th>Órgão/Lista</th><th>Status</th><th>Ocorrências</th></tr></thead>
            <tbody>
                @foreach($complianceEntries as $entry)
                    <tr>
                        <td>{{ $entry['title'] ?? '-' }}</td>
                        <td><span class="pill {{ ($entry['status'] ?? 'Regular') === 'Regular' ? 'ok' : 'warn' }}">{{ $entry['status'] ?? '-' }}</span></td>
                        <td>{{ $entry['quantity'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="section">
    <h2 class="section-title">Cadastro, Porte e Contatos</h2>
    <table class="table">
        <tr><td class="label">Atividade principal</td><td>{{ $business['main_activity'] ?? '-' }}</td></tr>
        <tr><td class="label">Atividade secundária</td><td>{{ $business['secondary_activity'] ?? '-' }}</td></tr>
        <tr><td class="label">Capital social</td><td>{{ $formatCurrencyBr($business['capital_social'] ?? '-') }}</td></tr>
        <tr><td class="label">Porte</td><td>{{ $business['porte'] ?? '-' }}</td></tr>
        <tr><td class="label">Faixa de faturamento</td><td>{{ $business['faixa_faturamento'] ?? '-' }}</td></tr>
        <tr><td class="label">Faturamento presumido</td><td>{{ $business['faturamento_presumido'] ?? '-' }}</td></tr>
        <tr><td class="label">E-mails</td><td>{{ is_array($contacts['emails'] ?? null) && $contacts['emails'] !== [] ? implode(' | ', $contacts['emails']) : '-' }}</td></tr>
        <tr><td class="label">Telefones</td><td>{{ is_array($contacts['phones'] ?? null) && $contacts['phones'] !== [] ? implode(' | ', $contacts['phones']) : '-' }}</td></tr>
        <tr><td class="label">Endereços</td><td>{{ is_array($contacts['addresses'] ?? null) && $contacts['addresses'] !== [] ? implode(' | ', $contacts['addresses']) : '-' }}</td></tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Comportamento de Crédito</h2>
    <table class="table">
        <tr><td class="label">Consultas últimos 30 dias</td><td>{{ $creditBehavior['ultimos_30_dias'] ?? ($consultationHistory['total_30'] ?? 0) }}</td></tr>
        <tr><td class="label">Consultas de 31 a 60 dias</td><td>{{ $creditBehavior['de_31_a_60_dias'] ?? ($consultationHistory['total_31_60'] ?? 0) }}</td></tr>
        <tr><td class="label">Consultas de 61 a 90 dias</td><td>{{ $creditBehavior['de_61_a_90_dias'] ?? ($consultationHistory['total_61_90'] ?? 0) }}</td></tr>
        <tr><td class="label">Consultas acima de 90 dias</td><td>{{ $creditBehavior['mais_90_dias'] ?? ($consultationHistory['total_90_plus'] ?? 0) }}</td></tr>
        <tr><td class="label">Cadastro positivo</td><td>{{ ($creditBehavior['status_cadastro_positivo'] ?? '') === '1' ? 'Ativo' : (($creditBehavior['status_cadastro_positivo'] ?? '') === '' ? '-' : 'Inativo') }}</td></tr>
    </table>

    @php $historyDetails = is_array($consultationHistory['details'] ?? null) ? $consultationHistory['details'] : []; @endphp
    @if($historyDetails !== [])
        <table class="table" style="margin-top: 8px;">
            <thead><tr><th>Data</th><th>Segmento</th><th>Consultas</th></tr></thead>
            <tbody>
                @foreach($historyDetails as $item)
                    <tr>
                        <td>{{ $item['date'] ?? '-' }}</td>
                        <td>{{ $item['segment'] ?? '-' }}</td>
                        <td>{{ $item['count'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="section">
    <h2 class="section-title">Negativações, Dívidas Públicas e Protestos</h2>
    <table class="table">
        <tr><td class="label">Controle de pendências</td><td>{{ $negatives['controle_pendencias_credito'] ?? '0' }}</td></tr>
        <tr><td class="label">Apontamentos</td><td>{{ $negatives['apontamentos'] ?? 0 }}</td></tr>
        <tr><td class="label">CCF</td><td>{{ $negatives['ccf'] ?? 0 }}</td></tr>
        <tr><td class="label">Ações judiciais (negativação)</td><td>{{ $negatives['acoes_judiciais'] ?? 0 }}</td></tr>
        <tr><td class="label">Total de protestos</td><td>{{ $negatives['protestos_total'] ?? '0' }}</td></tr>
        <tr><td class="label">Valor total protestado</td><td>{{ $negatives['protestos_valor'] ?? '0,00' }}</td></tr>
    </table>

    @if($publicDebts !== [])
        <table class="table" style="margin-top: 8px;">
            <thead><tr><th>Dívida Pública</th><th>Quantidade</th><th>Valor</th></tr></thead>
            <tbody>
                @foreach($publicDebts as $debt)
                    <tr>
                        <td>{{ $debt['title'] ?? '-' }}</td>
                        <td>{{ $debt['quantity'] ?? '0' }}</td>
                        <td>{{ $debt['value'] ?? '0,00' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@if($partners !== [])
    <div class="section">
        <h2 class="section-title">Quadro Societário</h2>
        <table class="table">
            <thead>
                <tr><th>Sócio</th><th>Documento</th><th>Tipo</th><th>Relação</th><th>Participação</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($partners as $partner)
                    <tr>
                        <td>{{ $partner['name'] ?? '-' }}</td>
                        <td>{{ $formatDocument($partner['document'] ?? '-') }}</td>
                        <td>{{ $partner['type'] ?? '-' }}</td>
                        <td>{{ $partner['relationship'] ?? '-' }}</td>
                        <td>{{ $partner['share'] ?? '-' }}</td>
                        <td>{{ $partner['status'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="table" style="margin-top: 8px;">
            <tr><td class="label">Grupo multiempresarial</td><td>{{ $patrimony['multiempresarial_count'] ?? 0 }}</td></tr>
            <tr><td class="label">Filiais identificadas</td><td>{{ $patrimony['filiais_count'] ?? 0 }}</td></tr>
        </table>
    </div>
@endif

@if($businessIndicators !== [])
    <div class="section">
        <h2 class="section-title">Indicadores de Negócio</h2>
        @foreach($businessIndicators as $group)
            <table class="table" style="margin-top: 8px;">
                <thead><tr><th colspan="3">{{ $group['title'] ?? '-' }}</th></tr><tr><th>Indicador</th><th>Risco</th><th>Descrição</th></tr></thead>
                <tbody>
                    @foreach(($group['items'] ?? []) as $indicator)
                        <tr>
                            <td>{{ $indicator['name'] ?? '-' }}</td>
                            <td>{{ $indicator['risk'] ?? '-' }}</td>
                            <td>{{ $indicator['description'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
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
                <thead><tr><th>Número</th><th>Tribunal</th><th>Classe</th><th>Status</th></tr></thead>
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
    <h2 class="section-title">Fontes Consultadas</h2>
    <table class="table">
        <thead>
            <tr><th>Fonte</th><th>Status</th><th>HTTP</th><th>Consultado em</th><th>Observação</th></tr>
        </thead>
        <tbody>
            @foreach($sources as $source)
                @php
                    $sourceNote = $sanitizeChargeMessage($source['message'] ?? '');
                    if ($sourceNote === '') {
                        $sourceNote = $sanitizeChargeMessage($source['error_message'] ?? '');
                    }
                    if ($sourceNote === '') {
                        $sourceNote = '-';
                    }
                @endphp
                <tr>
                    <td>{{ $source['title'] ?? ($source['key'] ?? '-') }}</td>
                    <td><span class="pill {{ ($source['status'] ?? 'error') === 'success' ? 'ok' : 'warn' }}">{{ $source['status_label'] ?? (($source['status'] ?? '') === 'success' ? 'Sucesso' : 'Falha') }}</span></td>
                    <td>{{ $source['http_status'] ?? '-' }}</td>
                    <td>{{ $source['consulted_at'] ?? '-' }}</td>
                    <td>
                        {{ $sourceNote }}
                        <div style="margin-top: 3px; color: #64748b;">{{ $source['endpoint'] ?? '-' }}</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="footer">
    Documento Oficial CPF CLEAN BR • CNPJ 44.156.681/0001-57
</div>
</body>
</html>
