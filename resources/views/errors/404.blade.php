@include('errors.error', [
    'code' => 404,
    'headline' => 'Página não encontrada',
    'title' => 'A rota que você tentou acessar não existe.',
    'description' => 'Confira o endereço digitado ou volte para a página inicial.',
    'visualText' => 'Não localizamos esta página no sistema.'
])
