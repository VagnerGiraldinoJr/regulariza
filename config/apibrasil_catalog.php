<?php

return [
    'providers' => [
        'bureau' => [
            'label' => 'Bureau de Credito',
            'driver' => 'apibrasil',
        ],
        'bacen' => [
            'label' => 'SCR / Bacen',
            'driver' => 'apibrasil',
        ],
        'fiscal' => [
            'label' => 'Regularidade Fiscal',
            'driver' => 'apibrasil',
        ],
        'registry' => [
            'label' => 'Cartorio / Protesto',
            'driver' => 'apibrasil',
        ],
    ],

    'bundles' => [
        'pf' => [
            'title' => 'Pesquisa PF Consolidada',
            'document_type' => 'cpf',
            'sources' => [
                [
                    'provider' => 'bureau',
                    'consultation_key' => 'acerta_essencial_plus_pf',
                ],
                [
                    'provider' => 'bacen',
                    'consultation_key' => 'scr_bacen_score_pf',
                ],
            ],
        ],
        'pj' => [
            'title' => 'Pesquisa PJ Consolidada',
            'document_type' => 'cnpj',
            'sources' => [
                [
                    'provider' => 'bacen',
                    'consultation_key' => 'scr_bacen_score_pj',
                ],
                [
                    'provider' => 'bureau',
                    'consultation_key' => 'serasa_premium_pj',
                ],
            ],
        ],
    ],

    'categories' => [
        'analise_credito' => 'Análise de Crédito',
        'analise_antifraude' => 'Análise Antifraude',
        'consulta_cnpj' => 'Consulta CNPJ',
        'regularidade_fiscal' => 'Regularidade Fiscal',
        'servicos_cartorio' => 'Serviços de Cartório',
    ],

    'consultations' => [
        'acerta_essencial_plus_pf' => [
            'title' => 'Acerta Essencial Plus PF',
            'category' => 'analise_credito',
            'document_type' => 'cpf',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cpf/credits',
            'body' => [
                /*
                 * O endpoint aceitou "acerta-essencial" nos testes reais.
                 * Quando a variante "plus" estiver liberada pela API Brasil,
                 * basta ajustar este tipo sem mexer no restante do fluxo.
                 */
                'tipo' => 'acerta-essencial',
                'cpf' => '{document}',
                'homolog' => false,
            ],
            'description' => 'Fonte principal PF para cadastro, renda, alertas, restrições resumidas e score comercial.',
        ],
        'credito_simples_pf' => [
            'title' => 'Crédito Simples PF',
            'category' => 'analise_credito',
            'document_type' => 'cpf',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cpf/credits',
            'body' => [
                'tipo' => 'credito-simples',
                'cpf' => '{document}',
                'homolog' => true,
            ],
            'description' => 'Consulta cadastral enxuta com contatos, endereços e dados básicos de pessoa física.',
        ],
        'spc_boa_vista_pf' => [
            'title' => 'SPC Boa Vista PF',
            'category' => 'analise_credito',
            'document_type' => 'cpf',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cpf/credits',
            'body' => [
                'tipo' => 'spc-boa-vista',
                'cpf' => '{document}',
                'homolog' => true,
            ],
            'description' => 'Consulta de restrições financeiras em pessoa física.',
        ],
        'scr_bacen_score_pf' => [
            'title' => 'SCR Bacen + Score PF',
            'category' => 'analise_antifraude',
            'document_type' => 'cpf',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cpf/credits',
            'body' => [
                'tipo' => 'scr-analitico-resumo-bacen',
                'cpf' => '{document}',
                'homolog' => true,
            ],
            'tipo_fallbacks' => [
                'scr-analitico-resumo-bacen',
                'scr_analitico_resumo_bacen',
                'scr-analitico-bacen',
                'scr_analitico_bacen',
                'scr-bacen-score',
                'scr_bacen_score',
            ],
            'description' => 'Consulta SCR Bacen e score para pessoa física.',
        ],
        'scr_bacen_score_pj' => [
            'title' => 'SCR Bacen + Score PJ',
            'category' => 'analise_antifraude',
            'document_type' => 'cnpj',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cnpj/credits',
            'body' => [
                'tipo' => 'scr-analitico-resumo-bacen',
                'cnpj' => '{document}',
                'homolog' => true,
            ],
            'tipo_fallbacks' => [
                'scr-analitico-resumo-bacen',
                'scr_analitico_resumo_bacen',
                'scr-analitico-resumo-bacen-pj',
                'scr_analitico_resumo_bacen_pj',
                'scr-analitico-bacen-pj',
                'scr_analitico_bacen_pj',
                'scr-bacen-score-pj',
                'scr_bacen_score_pj',
            ],
            'description' => 'Consulta SCR Bacen e score para pessoa jurídica.',
        ],
        'serasa_premium_pj' => [
            'title' => 'Serasa Premium PJ',
            'category' => 'consulta_cnpj',
            'document_type' => 'cnpj',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cnpj/credits',
            'body' => [
                'cnpj' => '{document}',
                'homolog' => true,
            ],
            'description' => 'Consulta completa Serasa Premium para CNPJ.',
        ],
        'compliance_basic_pj' => [
            'title' => 'Compliance Basic PJ',
            'category' => 'regularidade_fiscal',
            'document_type' => 'cnpj',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cnpj/credits',
            'body' => [
                'tipo' => 'compliance-basic-pj',
                'cnpj' => '{document}',
                'homolog' => true,
            ],
            'tipo_fallbacks' => [
                'compliance-basic-pj',
                'compliance_basic_pj',
                'compliance-basic',
                'compliance_basic',
            ],
            'description' => 'Compliance PJ enxuto (órgãos de controle, cadastro e contato empresarial).',
        ],
        'compliance_complete_pj' => [
            'title' => 'Compliance Complete PJ',
            'category' => 'regularidade_fiscal',
            'document_type' => 'cnpj',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cnpj/credits',
            'body' => [
                'tipo' => 'compliance-complete-pj',
                'cnpj' => '{document}',
                'homolog' => true,
            ],
            'tipo_fallbacks' => [
                'compliance-complete-pj',
                'compliance_complete_pj',
                'compliance-complete',
                'compliance_complete',
            ],
            'description' => 'Compliance PJ completo com score, indicadores, sócios, dívidas públicas e órgãos.',
        ],
        'serasa_score_pf' => [
            'title' => 'Serasa Score PF',
            'category' => 'analise_credito',
            'document_type' => 'cpf',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cpf/serasa-score',
            'body' => ['cpf' => '{document}'],
            'description' => 'Score PF para avaliação de risco.',
        ],
        'certidao_negativa_pf' => [
            'title' => 'Certidão Negativa PF',
            'category' => 'regularidade_fiscal',
            'document_type' => 'cpf',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cpf/certidao-negativa',
            'body' => ['cpf' => '{document}'],
            'description' => 'Certidão negativa para pessoa física.',
        ],
        'certidao_negativa_pj' => [
            'title' => 'Certidão Negativa PJ',
            'category' => 'regularidade_fiscal',
            'document_type' => 'cnpj',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cnpj/certidao-negativa',
            'body' => ['cnpj' => '{document}'],
            'description' => 'Certidão negativa para pessoa jurídica.',
        ],
        'define_risco_pj' => [
            'title' => 'Define Risco PJ',
            'category' => 'analise_credito',
            'document_type' => 'cnpj',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cnpj/credits',
            'body' => [
                'tipo' => 'define-risco-pj',
                'cnpj' => '{document}',
                'homolog' => true,
            ],
            'description' => 'Consulta sintética de risco PJ com score, dados cadastrais e indícios de negativas/protestos.',
        ],
        'limite_pj' => [
            'title' => 'Limite PJ',
            'category' => 'analise_credito',
            'document_type' => 'cnpj',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cnpj/credits',
            'body' => [
                'tipo' => 'limite-pj',
                'cnpj' => '{document}',
                'homolog' => true,
            ],
            'description' => 'Consulta de limite/score PJ para apoio à decisão de crédito.',
        ],
        'protesto_nacional_v2' => [
            'title' => 'Protesto Nacional V2',
            'category' => 'servicos_cartorio',
            'document_type' => 'both',
            'method' => 'POST',
            'path' => '/api/v2/consulta/protesto/nacional-v2',
            'body' => ['document' => '{document}'],
            'description' => 'Pesquisa de protestos em abrangência nacional.',
        ],
    ],
];
