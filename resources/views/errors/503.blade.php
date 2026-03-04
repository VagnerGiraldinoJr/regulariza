@include('errors.error', [
    'code' => 503,
    'headline' => 'Serviço indisponível',
    'title' => 'Estamos em manutenção ou temporariamente indisponíveis.',
    'description' => 'Tente novamente em alguns minutos.',
    'visualText' => 'Serviço temporariamente fora do ar.'
])
