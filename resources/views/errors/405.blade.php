@include('errors.error', [
    'code' => 405,
    'headline' => 'Método não permitido',
    'title' => 'A operação solicitada não é aceita nesta rota.',
    'description' => 'Tente novamente pelo fluxo correto do sistema.',
    'visualText' => 'Método HTTP inválido para este endpoint.'
])
