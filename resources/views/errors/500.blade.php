@include('errors.error', [
    'code' => 500,
    'headline' => 'Erro interno',
    'title' => 'Aconteceu um erro inesperado no servidor.',
    'description' => 'Nossa equipe já pode investigar este evento. Tente novamente em instantes.',
    'visualText' => 'Falha interna ao processar sua solicitação.'
])
