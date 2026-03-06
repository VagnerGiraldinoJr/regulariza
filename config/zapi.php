<?php

return [
    'instance' => env('ZAPI_INSTANCE'),
    'token' => env('ZAPI_TOKEN'),
    'client_token' => env('ZAPI_CLIENT_TOKEN'),
    'templates' => [
        'boas_vindas' => 'Olá {nome}! Recebemos o pagamento da pesquisa (R$ 200,00) do protocolo {protocolo}. Em horário comercial, um analista vai te chamar por aqui com o retorno da análise do CPF/CNPJ.',
        'portal_acesso' => 'Olá {nome}! Seu contrato foi confirmado e seu acesso ao portal foi liberado. Link: {link} | Login: {email} | Senha temporária: {senha}. Altere sua senha no primeiro acesso.',
        'status_atualizado' => 'Atualização do seu protocolo {protocolo}: {status}',
        'conclusao' => 'Seu processo de regularização {protocolo} foi concluído! ✅',
        'avaliacao_atendimento' => 'Olá {nome}! Seu atendimento no protocolo {protocolo} foi finalizado. Sua opinião é muito importante: de 0 a 10, qual nota você dá para nosso atendimento? Se quiser, também nos conte em uma frase como podemos melhorar.',
    ],
    'media' => [
        'boas_vindas_image_url' => env('ZAPI_WELCOME_IMAGE_URL'),
    ],
];
