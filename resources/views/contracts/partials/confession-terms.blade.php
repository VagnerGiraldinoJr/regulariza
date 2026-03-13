@php
    $sortedInstallments = $contract->installments->sortBy('installment_number')->values();
    $entryInstallment = $sortedInstallments->firstWhere('installment_number', 0);
    $remainingInstallments = $sortedInstallments->filter(fn ($installment) => (int) $installment->installment_number > 0)->values();
    $foro = trim((string) config('app.server_city', 'Ponta Grossa')).' / '.trim((string) config('app.server_uf', 'PR'));
    $serviceDescription = trim((string) ($contract->order?->service?->descricao ?: 'Assessoria consultiva, diagnóstico cadastral e condução operacional do processo de regularização contratado pelo cliente.'));
@endphp

<div class="contract-terms">
    <h2>Confissão de Dívida</h2>
    <p class="contract-terms__intro">Instrumento particular com força de título executivo extrajudicial.</p>

    <div class="contract-terms__block">
        <p><strong>CREDORA:</strong> VEX INVEST LTDA / CPF Clean Brasil, inscrita no CNPJ nº 44.156.681/0001-57, operação administrativa da CPF Clean Brasil - email: contato@cpfclean.com.br</p>
        <p><strong>DEVEDOR(A):</strong> {{ $contract->user?->name ?: '-' }}, CPF/CNPJ {{ $contract->user?->cpf_cnpj ?: '-' }}, e-mail {{ $contract->user?->email ?: '-' }}, telefone {{ $contract->user?->whatsapp ?: '-' }}.</p>
    </div>

    <div class="contract-terms__clause">
        <h3>Cláusula 1 – Da origem da dívida</h3>
        <p>O(A) devedor(a) declara que contratou a credora para prestação de serviços consistentes em {{ $serviceDescription }}</p>
        <p>Em razão dos serviços prestados e das condições comerciais apresentadas neste contrato, reconhece e confessa dever à credora o valor líquido, certo e exigível de <strong>R$ {{ number_format((float) $contract->fee_amount, 2, ',', '.') }}</strong>, referente aos honorários contratados.</p>
    </div>

    <div class="contract-terms__clause">
        <h3>Cláusula 2 – Da forma de pagamento</h3>
        <p>O valor confessado será pago da seguinte forma:</p>
        <ul>
            @if($entryInstallment)
                <li>{{ $entryInstallment->label }} no valor de R$ {{ number_format((float) $entryInstallment->amount, 2, ',', '.') }}, com vencimento em {{ $entryInstallment->due_date?->format('d/m/Y') ?: '-' }}, via {{ $entryInstallment->billing_type }}.</li>
            @endif
            @foreach($remainingInstallments as $installment)
                <li>{{ $installment->label }} no valor de R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}, com vencimento em {{ $installment->due_date?->format('d/m/Y') ?: '-' }}, via {{ $installment->billing_type }}.</li>
            @endforeach
        </ul>
    </div>

    <div class="contract-terms__clause">
        <h3>Cláusula 3 – Do inadimplemento</h3>
        <p>O não pagamento de qualquer parcela na data do vencimento implicará vencimento antecipado das demais parcelas, multa moratória de 2%, juros de mora de 1% ao mês pro rata die, correção monetária pelo IPCA e honorários advocatícios entre 10% e 20% sobre o valor atualizado da dívida em caso de cobrança judicial.</p>
    </div>

    <div class="contract-terms__clause">
        <h3>Cláusula 4 – Da confissão irrevogável e irretratável</h3>
        <p>O(A) devedor(a) reconhece expressamente que a presente dívida é líquida, certa e exigível, renunciando a qualquer alegação futura quanto à sua origem ou ao seu valor.</p>
    </div>

    <div class="contract-terms__clause">
        <h3>Cláusula 5 – Do título executivo</h3>
        <p>O presente instrumento constitui título executivo extrajudicial, nos termos do art. 784, III do Código de Processo Civil, apto a embasar execução judicial independentemente de ação de conhecimento.</p>
    </div>

    <div class="contract-terms__clause">
        <h3>Cláusula 6 – Do foro</h3>
        <p>Fica eleito o foro da comarca de {{ $foro }}, com renúncia de qualquer outro, por mais privilegiado que seja.</p>
    </div>
    
</div>
