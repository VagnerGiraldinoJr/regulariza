<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Dossiê PF Consolidado - CPF Clean Brasil</title>
    <style>
        * { box-sizing: border-box; }
        @page { margin: 18px 20px 24px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #102235; font-size: 11px; }
        .header {
            background: #0f3d59;
            color: #fff;
            padding: 12px 14px;
            border-radius: 10px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .title { margin: 0; font-size: 23px; font-weight: 700; letter-spacing: .02em; }
        .subtitle { margin: 4px 0 0; font-size: 10px; color: #cfeeff; }
        .protocol { margin-top: 8px; font-size: 9px; color: #d9f3ff; }
        .section { margin-top: 14px; }
        .section-title {
            margin: 0 0 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #d9e4ef;
            color: #17607e;
            font-size: 14px;
            font-weight: 700;
        }
        .table { width: 100%; border-collapse: collapse; }
        .table td, .table th { border: 1px solid #e3ebf3; padding: 6px 8px; vertical-align: top; }
        .table th { background: #eef7fb; color: #36536b; text-align: left; }
        .label { width: 185px; background: #f8fbfd; color: #48627a; font-weight: 700; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 -8px; }
        .card {
            border: 1px solid #dce7ef;
            border-radius: 10px;
            background: #f8fbfd;
            padding: 10px;
            min-height: 72px;
        }
        .card-label { font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; }
        .card-value { margin-top: 6px; font-size: 24px; font-weight: 700; color: #0f3d59; }
        .card-value.small { font-size: 16px; line-height: 1.25; }
        .pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .pill.safe { background: #e6f8ec; color: #17603a; }
        .pill.warn { background: #fff0d8; color: #8a5305; }
        .pill.danger { background: #fee6e2; color: #8e2218; }
        .muted { color: #64748b; }
        .grid-two { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 -8px; }
        .grid-two td { width: 50%; vertical-align: top; }
        .list { margin: 0; padding-left: 16px; }
        .list li { margin-bottom: 4px; }
        .note {
            margin-top: 8px;
            padding: 8px 10px;
            border: 1px solid #dce7ef;
            border-radius: 8px;
            background: #f8fbfd;
            color: #355066;
            font-size: 10px;
            line-height: 1.45;
        }
        .footer {
            margin-top: 18px;
            border-top: 1px solid #d9e4ef;
            padding-top: 8px;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body>
@php
    $referenceId = $order->id ?: null;
    if ($referenceId === null && isset($report['meta']['commercial_protocol'])) {
        $referenceId = preg_replace('/\D+/', '', (string) $report['meta']['commercial_protocol']);
    }
    $referenceId = $referenceId ?: '000001';
    $person = array_replace([
        'name' => '-',
        'document' => '-',
        'cpf_status' => '-',
        'rfb_status' => '-',
        'birth_date' => '-',
        'age' => '-',
        'faixa_idade' => '-',
        'gender' => '-',
        'mother_name' => '-',
        'rg' => '-',
        'title' => '-',
        'marital_status' => '-',
        'education' => '-',
        'education_level' => '-',
        'region' => '-',
        'uf' => '-',
        'zodiac' => '-',
        'dependents' => '-',
    ], $report['person'] ?? []);
    $contacts = array_replace([
        'main_email' => '-',
        'emails' => [],
        'phones' => [],
        'employer' => [],
        'history' => [],
    ], $report['contacts'] ?? []);
    $score = array_replace([
        'value' => '-',
        'source' => '-',
        'band' => '-',
        'message' => '-',
        'probability' => '-',
        'raw_rating_value' => null,
        'rating' => [],
    ], $report['score'] ?? []);
    $rating = array_replace([
        'classification' => '-',
        'moodys' => '-',
        'sp' => '-',
        'fitch' => '-',
    ], $score['rating'] ?? []);
    $scr = array_replace([
        'database' => '-',
        'relationship_since' => '-',
        'institutions' => '0',
        'operations_count' => '0',
        'summary' => [
            'credito_a_vencer' => '0,00',
            'credito_vencido' => '0,00',
            'limite_credito' => '0,00',
            'prejuizo' => '0,00',
        ],
        'operations' => [],
    ], $report['scr'] ?? []);
    $restrictions = array_replace([
        'count' => 0,
        'total' => '',
        'oldest' => '-',
        'latest' => '-',
        'occurrences' => [],
        'alerts' => [],
    ], $report['restrictions'] ?? []);
    $protests = array_replace([
        'count' => 0,
        'total' => '',
        'oldest' => '-',
        'latest' => '-',
        'records' => [],
    ], $report['protests'] ?? []);
    $addresses = is_array($report['addresses'] ?? null) ? $report['addresses'] : [];
    $income = array_replace([
        'presumed_income' => '-',
        'presumed_income_label' => '-',
    ], $report['income'] ?? []);
    $summary = array_replace([
        'risk' => 'Moderado',
        'conclusion' => '-',
    ], $report['summary'] ?? []);

    $formatMoney = function ($value): string {
        if (! is_string($value) && ! is_numeric($value)) {
            return '-';
        }

        $normalized = preg_replace('/[^\d,.\-]/', '', (string) $value);
        if ($normalized === null || $normalized === '') {
            return '-';
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? 'R$ '.number_format((float) $normalized, 2, ',', '.') : (string) $value;
    };

    $riskPill = match ($summary['risk']) {
        'Baixo' => 'pill safe',
        'Moderado' => 'pill warn',
        default => 'pill danger',
    };
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td>
                <p class="title">DIAGNÓSTICO FINANCEIRO PF</p>
                <p class="subtitle">Consolidado priorizando Acerta Essencial Plus e SCR Bacen</p>
                <div class="protocol">
                    Protocolo comercial: {{ $report['meta']['commercial_protocol'] }}<br>
                    Protocolo API: {{ $report['meta']['api_protocol'] }}<br>
                    Referência do relatório: CPFBR-PF-{{ str_pad((string) $referenceId, 6, '0', STR_PAD_LEFT) }}<br>
                    Consultado em: {{ $report['meta']['consultation_date'] }}<br>
                    Emitido em: {{ $report['meta']['generated_at']->format('d/m/Y H:i:s') }}
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Dados Cadastrais</h2>
    <table class="table">
        <tr><td class="label">Nome Completo</td><td>{{ $person['name'] ?: '-' }}</td></tr>
        <tr><td class="label">CPF</td><td>{{ $person['document'] ?: '-' }}</td></tr>
        <tr><td class="label">Situação CPF</td><td>{{ $person['cpf_status'] ?: '-' }}</td></tr>
        <tr><td class="label">Status RFB</td><td>{{ $person['rfb_status'] ?: '-' }}</td></tr>
        <tr><td class="label">Data de Nascimento</td><td>{{ $person['birth_date'] ?: '-' }}</td></tr>
        <tr><td class="label">Idade / Faixa Etária</td><td>{{ $person['age'] ?: '-' }} / {{ $person['faixa_idade'] ?: '-' }}</td></tr>
        <tr><td class="label">Sexo</td><td>{{ $person['gender'] ?: '-' }}</td></tr>
        <tr><td class="label">Nome da Mãe</td><td>{{ $person['mother_name'] ?: '-' }}</td></tr>
        <tr><td class="label">RG / Título</td><td>{{ $person['rg'] ?: '-' }} / {{ $person['title'] ?: '-' }}</td></tr>
        <tr><td class="label">Estado Civil</td><td>{{ $person['marital_status'] ?: '-' }}</td></tr>
        <tr><td class="label">Escolaridade</td><td>{{ $person['education'] !== '-' ? $person['education'] : $person['education_level'] }}</td></tr>
        <tr><td class="label">Região / UF</td><td>{{ $person['region'] ?: '-' }} / {{ $person['uf'] ?: '-' }}</td></tr>
        <tr><td class="label">Signo</td><td>{{ $person['zodiac'] ?: '-' }}</td></tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Indicadores Financeiros</h2>
    <table class="cards">
        <tr>
            <td>
                <div class="card">
                    <div class="card-label">Score Principal</div>
                    <div class="card-value">{{ $score['value'] ?: '-' }}</div>
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
                    <div class="card-label">Faixa SCR</div>
                    <div class="card-value small">{{ $score['band'] ?: '-' }}</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="card-label">Risco Consolidado</div>
                    <div class="card-value small"><span class="{{ $riskPill }}">{{ $summary['risk'] }}</span></div>
                </div>
            </td>
        </tr>
    </table>

    <div class="note">
        <strong>Fonte do score:</strong> {{ $score['source'] ?: '-' }}<br>
        <strong>Probabilidade API:</strong> {{ $score['probability'] ?: '-' }}
        @if(($score['raw_rating_value'] ?? null) && (string) $score['raw_rating_value'] !== (string) $score['value'])
            <br><strong>Score rating auxiliar:</strong> {{ $score['raw_rating_value'] }}
        @endif
        @if(($score['message'] ?? '-') !== '-')
            <br><strong>Mensagem comercial:</strong> {{ $score['message'] }}
        @endif
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
        <tr>
            <td class="label">Conclusão</td>
            <td colspan="3">{{ $summary['conclusion'] }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Resumo Financeiro</h2>
    <table class="table">
        <tr><td class="label">Renda Presumida</td><td>{{ $income['presumed_income_label'] !== '-' ? $income['presumed_income_label'] : $formatMoney($income['presumed_income']) }}</td></tr>
        <tr><td class="label">Restrições Comerciais</td><td>{{ $restrictions['count'] }} ocorrência(s) / {{ $restrictions['total'] !== '' ? $formatMoney($restrictions['total']) : '-' }}</td></tr>
        <tr><td class="label">Protestos</td><td>{{ $protests['count'] }} ocorrência(s) / {{ $protests['total'] !== '' ? $formatMoney($protests['total']) : '-' }}</td></tr>
        <tr><td class="label">Instituições no SCR</td><td>{{ $scr['institutions'] }}</td></tr>
        <tr><td class="label">Operações no SCR</td><td>{{ $scr['operations_count'] }}</td></tr>
        <tr><td class="label">Créditos a Vencer</td><td>{{ $formatMoney($scr['summary']['credito_a_vencer']) }}</td></tr>
        <tr><td class="label">Créditos Vencidos</td><td>{{ $formatMoney($scr['summary']['credito_vencido']) }}</td></tr>
        <tr><td class="label">Limite de Crédito</td><td>{{ $formatMoney($scr['summary']['limite_credito']) }}</td></tr>
        <tr><td class="label">Prejuízo</td><td>{{ $formatMoney($scr['summary']['prejuizo']) }}</td></tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Contatos e Endereços</h2>
    <table class="grid-two">
        <tr>
            <td>
                <table class="table">
                    <tr><th>Contato e Vínculos</th></tr>
                    <tr>
                        <td>
                            <strong>E-mail principal:</strong> {{ $contacts['main_email'] ?: '-' }}<br>
                            <strong>Dependentes:</strong> {{ $person['dependents'] ?: '-' }}<br>
                            @if($contacts['emails'] !== [])
                                <div style="margin-top: 6px;"><strong>E-mails</strong></div>
                                <ul class="list">
                                    @foreach($contacts['emails'] as $email)
                                        <li>{{ $email }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if($contacts['phones'] !== [])
                                <div style="margin-top: 6px;"><strong>Telefones</strong></div>
                                <ul class="list">
                                    @foreach($contacts['phones'] as $phone)
                                        <li>{{ $phone }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if($contacts['employer'] !== [])
                                <div style="margin-top: 6px;"><strong>Empregadores / Vínculos</strong></div>
                                <ul class="list">
                                    @foreach($contacts['employer'] as $employer)
                                        <li>{{ $employer }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="table">
                    <tr><th>Endereços Cadastrados</th></tr>
                    <tr>
                        <td>
                            @if($addresses !== [])
                                <ul class="list">
                                    @foreach($addresses as $address)
                                        <li>{{ $address }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="muted">Sem endereços retornados nas fontes consultadas.</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <h2 class="section-title">Ocorrências Comerciais (Acerta)</h2>
    <table class="grid-two">
        <tr>
            <td>
                <table class="table">
                    <tr><th colspan="2">Restrições Financeiras</th></tr>
                    <tr><td class="label">Quantidade</td><td>{{ $restrictions['count'] }}</td></tr>
                    <tr><td class="label">Valor Total</td><td>{{ $restrictions['total'] !== '' ? $formatMoney($restrictions['total']) : '-' }}</td></tr>
                    <tr><td class="label">Primeiro Registro</td><td>{{ $restrictions['oldest'] ?: '-' }}</td></tr>
                    <tr><td class="label">Último Registro</td><td>{{ $restrictions['latest'] ?: '-' }}</td></tr>
                    <tr>
                        <td colspan="2">
                            @if($restrictions['occurrences'] !== [])
                                @foreach($restrictions['occurrences'] as $occurrence)
                                    <div style="margin-bottom: 6px;">
                                        <strong>{{ $occurrence['creditor'] }}</strong><br>
                                        Contrato: {{ $occurrence['contract'] }}<br>
                                        Modalidade: {{ $occurrence['modality'] }}<br>
                                        Inclusão: {{ $occurrence['inclusion_date'] }} | Vencimento: {{ $occurrence['due_date'] }}<br>
                                        Origem: {{ $occurrence['origin'] }} | Valor: {{ $formatMoney($occurrence['value']) }}
                                    </div>
                                @endforeach
                            @else
                                <span class="muted">Sem ocorrências detalhadas no retorno principal do Acerta.</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="table">
                    <tr><th colspan="2">Protestos</th></tr>
                    <tr><td class="label">Quantidade</td><td>{{ $protests['count'] }}</td></tr>
                    <tr><td class="label">Valor Total</td><td>{{ $protests['total'] !== '' ? $formatMoney($protests['total']) : '-' }}</td></tr>
                    <tr><td class="label">Primeiro Registro</td><td>{{ $protests['oldest'] ?: '-' }}</td></tr>
                    <tr><td class="label">Último Registro</td><td>{{ $protests['latest'] ?: '-' }}</td></tr>
                    <tr>
                        <td colspan="2">
                            @if($protests['records'] !== [])
                                @foreach($protests['records'] as $record)
                                    <div style="margin-bottom: 6px;">
                                        <strong>{{ $record['cartorio'] }}</strong><br>
                                        Data: {{ $record['date'] }}<br>
                                        Situação: {{ $record['status'] }}<br>
                                        Valor: {{ $formatMoney($record['value']) }}
                                    </div>
                                @endforeach
                            @else
                                <span class="muted">Sem protestos detalhados no retorno principal do Acerta.</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if($restrictions['alerts'] !== [] || $contacts['history'] !== [])
        <table class="grid-two" style="margin-top: 8px;">
            <tr>
                <td>
                    <table class="table">
                        <tr><th>Alertas da Consulta</th></tr>
                        <tr>
                            <td>
                                @if($restrictions['alerts'] !== [])
                                    <ul class="list">
                                        @foreach($restrictions['alerts'] as $alert)
                                            <li>{{ $alert }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="muted">Sem alertas adicionais retornados.</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="table">
                        <tr><th>Histórico de Consultas</th></tr>
                        <tr>
                            <td>
                                @if($contacts['history'] !== [])
                                    <ul class="list">
                                        @foreach($contacts['history'] as $history)
                                            <li>{{ $history }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="muted">Sem histórico adicional retornado.</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif
</div>

<div class="section">
    <h2 class="section-title">SCR - Carteira de Crédito (Bacen)</h2>
    <table class="table">
        <tr><td class="label">Referência SCR</td><td>{{ $scr['database'] }}</td></tr>
        <tr><td class="label">Relacionamento desde</td><td>{{ $scr['relationship_since'] }}</td></tr>
        <tr><td class="label">Instituições</td><td>{{ $scr['institutions'] }}</td></tr>
        <tr><td class="label">Operações</td><td>{{ $scr['operations_count'] }}</td></tr>
    </table>

    @if($scr['operations'] !== [])
        <table class="table" style="margin-top: 8px;">
            <tr>
                <th>Modalidade</th>
                <th>Submodalidade</th>
                <th>Percentual</th>
                <th>Total</th>
            </tr>
            @foreach($scr['operations'] as $operation)
                <tr>
                    <td>{{ $operation['modalidade'] }}</td>
                    <td>{{ $operation['sub_modalidade'] }}</td>
                    <td>{{ $operation['percentual'] }}%</td>
                    <td>{{ $formatMoney($operation['total']) }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

<div class="section">
    <h2 class="section-title">Fontes Executadas no Dossiê</h2>
    <table class="table">
        <tr>
            <th>Fonte</th>
            <th>Status</th>
            <th>HTTP</th>
        </tr>
        @foreach($consultations as $consultation)
            <tr>
                <td>{{ $consultation->consultation_title ?: $consultation->consultation_key ?: 'Fonte' }}</td>
                <td>{{ strtoupper((string) $consultation->status) }}</td>
                <td>{{ $consultation->http_status ?: '-' }}</td>
            </tr>
        @endforeach
    </table>
</div>

<div class="footer">
    Documento Oficial CPF CLEAN BR<br>
    CPF Clean Brasil • CNPJ 44.156.681/0001-57<br>
    Este relatório consolida o bundle PF executado no sistema. A matriz de rating por letras é interna e pode ser rebalanceada sem alterar o fluxo operacional.
</div>
</body>
</html>
