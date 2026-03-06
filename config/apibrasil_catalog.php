<?php

return [
    'categories' => [
        'analise_credito' => 'Análise de Crédito',
        'analise_antifraude' => 'Análise Antifraude',
        'consulta_cnpj' => 'Consulta CNPJ',
        'regularidade_fiscal' => 'Regularidade Fiscal',
        'servicos_cartorio' => 'Serviços de Cartório',
    ],

    'consultations' => [
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
            'description' => 'Consulta SCR Bacen e score para pessoa jurídica.',
        ],
        'serasa_premium_pj' => [
            'title' => 'Serasa Premium PJ',
            'category' => 'consulta_cnpj',
            'document_type' => 'cnpj',
            'method' => 'POST',
            'path' => '/api/v2/consulta/cnpj/credits',
            'body' => [
                'tipo' => 'serasa-premium-pj',
                'cnpj' => '{document}',
                'homolog' => true,
            ],
            'description' => 'Consulta completa Serasa Premium para CNPJ.',
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
