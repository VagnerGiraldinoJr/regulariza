@include('errors.error', [
    'code' => 400,
    'headline' => 'Requisição inválida',
    'title' => 'Não conseguimos processar os dados enviados.',
    'description' => 'Revise as informações e tente novamente. Se necessário, recarregue a página.',
    'visualText' => 'A requisição enviada não está no formato esperado.'
])
