@include('errors.error', [
    'code' => 429,
    'headline' => 'Muitas tentativas',
    'title' => 'Você realizou muitas requisições em pouco tempo.',
    'description' => 'Aguarde alguns instantes antes de tentar novamente.',
    'visualText' => 'Proteção de limite de requisições ativada.'
])
