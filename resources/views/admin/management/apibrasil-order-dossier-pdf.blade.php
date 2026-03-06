<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Dossie de Consultas - CPF Clean Brasil</title>
    <style>
        * { box-sizing: border-box; }
        @page { margin: 18px 20px 24px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .header {
            background: #1f78b4;
            color: #fff;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 12px;
        }
        .title { margin: 0; font-size: 18px; font-weight: 700; }
        .subtitle { margin: 4px 0 0; font-size: 10px; color: #d7edff; }
        .meta { margin-top: 8px; font-size: 10px; color: #e8f4ff; }
        .section-title {
            margin: 0 0 7px;
            padding-bottom: 4px;
            border-bottom: 1px solid #dce4ec;
            font-size: 13px;
            font-weight: 700;
            color: #1f5f96;
        }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .table td { border: 1px solid #e5ecf3; padding: 6px 7px; vertical-align: top; }
        .label { width: 180px; font-weight: 700; color: #60758a; background: #f8fbff; }
        .status-ok { color: #177a2d; font-weight: 700; }
        .consulta {
            border: 1px solid #dbe5ef;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .consulta h3 {
            margin: 0 0 7px;
            font-size: 12px;
            font-weight: 700;
            color: #1f5f96;
        }
        .json {
            margin-top: 6px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #f8fbff;
            font-size: 9px;
            line-height: 1.3;
            color: #334155;
            padding: 8px;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 300px;
            overflow: hidden;
        }
        .footer {
            margin-top: 8px;
            border-top: 1px solid #dce4ec;
            padding-top: 6px;
            font-size: 9px;
            color: #64748b;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            background: #dcfce7;
            color: #166534;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
    </style>
</head>
<body>
@php
    $reportProtocol = 'CPFBR-DOSSIE-'.now()->format('Ymd').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
    $logoPath = public_path('assets/branding/cpfclean-logo.svg');
    $logoSvg = file_exists($logoPath) ? file_get_contents($logoPath) : null;
    $logoDataUri = $logoSvg ? 'data:image/svg+xml;base64,'.base64_encode($logoSvg) : null;
@endphp

<div class="header">
    <table style="width:100%; border-collapse: collapse;">
        <tr>
            <td style="width:50px; vertical-align: top;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="CPF Clean Brasil" style="width:36px; height:36px;">
                @endif
            </td>
            <td>
                <p class="title">Dossie de Consultas Financeiras</p>
                <p class="subtitle">Consolidado de analises para um unico pedido</p>
                <div class="meta">
                    Protocolo Comercial: {{ $order->protocolo ?: '-' }}<br>
                    Cliente: {{ $order->user?->name ?: '-' }}<br>
                    Documento: {{ preg_replace('/\D+/', '', (string) ($order->lead?->cpf_cnpj ?: $order->user?->cpf_cnpj ?: '-')) }}<br>
                    Protocolo do Documento: {{ $reportProtocol }}<br>
                    Emitido em: {{ now()->format('d/m/Y H:i:s') }}
                </div>
            </td>
        </tr>
    </table>
</div>

<h2 class="section-title">Resumo</h2>
<table class="table">
    <tr>
        <td class="label">Total de consultas no pedido</td>
        <td>{{ $consultations->count() }}</td>
    </tr>
    <tr>
        <td class="label">Status</td>
        <td><span class="badge">Consultas validas para analise comercial</span></td>
    </tr>
</table>

<h2 class="section-title">Consultas</h2>
@foreach ($consultations as $item)
    <div class="consulta">
        <h3>{{ $item->consultation_title ?: 'Consulta API Brasil' }}</h3>
        <table class="table" style="margin-bottom: 0;">
            <tr>
                <td class="label">Documento</td>
                <td>{{ strtoupper((string) $item->document_type) }} {{ $item->document_number }}</td>
            </tr>
            <tr>
                <td class="label">Data da consulta</td>
                <td>{{ $item->created_at?->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td class="label">Status tecnico</td>
                <td class="status-ok">SUCESSO (HTTP {{ $item->http_status ?: '200' }})</td>
            </tr>
            <tr>
                <td class="label">Endpoint</td>
                <td>{{ $item->endpoint ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Analista vinculado</td>
                <td>{{ $item->analyst?->name ?: 'Nao encaminhado' }}</td>
            </tr>
        </table>
        <div class="json">{{ json_encode($item->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</div>
    </div>
@endforeach

<div class="footer">
    Documento Oficial CPF CLEAN BR<br>
    CPF Clean Brasil • CNPJ 44.156.681/0001-57<br>
    Protocolo {{ $reportProtocol }}
</div>
</body>
</html>
