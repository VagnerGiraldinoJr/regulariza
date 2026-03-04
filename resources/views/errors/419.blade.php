@include('errors.error', [
    'code' => 419,
    'headline' => 'Sessão expirada',
    'title' => 'Sua sessão expirou por segurança.',
    'description' => 'Atualize a página e faça login novamente para continuar.',
    'visualText' => 'O token de segurança desta operação expirou.'
])
