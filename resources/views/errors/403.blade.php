@include('errors.error', [
    'code' => 403,
    'headline' => 'Acesso negado',
    'title' => 'Você não tem permissão para acessar este recurso.',
    'description' => 'Se acredita que isso é um erro, fale com o administrador do sistema.',
    'visualText' => 'Seu perfil atual não tem autorização para esta ação.'
])
