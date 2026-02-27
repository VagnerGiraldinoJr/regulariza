<?php

return [
    'instance' => env('ZAPI_INSTANCE'),
    'token' => env('ZAPI_TOKEN'),
    'client_token' => env('ZAPI_CLIENT_TOKEN'),
    'templates' => [
        'boas_vindas' => 'Olá {nome}! Seu pedido {protocolo} foi confirmado. Acesse seu portal: {link}',
        'status_atualizado' => 'Atualização do seu protocolo {protocolo}: {status}',
        'conclusao' => 'Seu processo de regularização {protocolo} foi concluído! ✅',
    ],
];
