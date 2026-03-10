<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Matriz interna de rating por score
    |--------------------------------------------------------------------------
    |
    | Esta tabela não vem da API. Ela traduz o score numérico em uma
    | classificação legível para o relatório comercial. O balanceamento pode
    | ser ajustado depois sem alterar o layout do PDF.
    |
    */
    'matrix' => [
        [
            'min' => 901,
            'max' => 1000,
            'classification' => 'GRAU DE INVESTIMENTO',
            'moodys' => 'Aaa',
            'sp' => 'AAA',
            'fitch' => 'AAA',
        ],
        [
            'min' => 801,
            'max' => 900,
            'classification' => 'GRAU DE INVESTIMENTO',
            'moodys' => 'Aa2',
            'sp' => 'AA',
            'fitch' => 'AA',
        ],
        [
            'min' => 701,
            'max' => 800,
            'classification' => 'GRAU DE INVESTIMENTO',
            'moodys' => 'A2',
            'sp' => 'A',
            'fitch' => 'A',
        ],
        [
            'min' => 601,
            'max' => 700,
            'classification' => 'GRAU DE INVESTIMENTO',
            'moodys' => 'Baa2',
            'sp' => 'BBB',
            'fitch' => 'BBB',
        ],
        [
            'min' => 501,
            'max' => 600,
            'classification' => 'GRAU ESPECULATIVO',
            'moodys' => 'Ba2',
            'sp' => 'BB',
            'fitch' => 'BB',
        ],
        [
            'min' => 401,
            'max' => 500,
            'classification' => 'GRAU ESPECULATIVO',
            'moodys' => 'B2',
            'sp' => 'B',
            'fitch' => 'B',
        ],
        [
            'min' => 301,
            'max' => 400,
            'classification' => 'GRAU ESPECULATIVO',
            'moodys' => 'Caa2',
            'sp' => 'CCC',
            'fitch' => 'CCC',
        ],
        [
            'min' => 201,
            'max' => 300,
            'classification' => 'GRAU ESPECULATIVO',
            'moodys' => 'Ca',
            'sp' => 'CC',
            'fitch' => 'CC',
        ],
        [
            'min' => 101,
            'max' => 200,
            'classification' => 'GRAU ESPECULATIVO',
            'moodys' => 'C',
            'sp' => 'C',
            'fitch' => 'C',
        ],
        [
            'min' => 0,
            'max' => 100,
            'classification' => 'GRAU ESPECULATIVO',
            'moodys' => 'C',
            'sp' => 'D',
            'fitch' => 'DDD',
        ],
    ],
];
