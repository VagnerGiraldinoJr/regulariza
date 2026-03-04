@include('errors.error', [
    'code' => 401,
    'headline' => 'Acesso não autorizado',
    'title' => 'Você precisa fazer login para continuar.',
    'description' => 'Entre com suas credenciais e tente novamente.',
    'visualText' => 'Esta área exige autenticação.'
])
