<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'payments' => [
        'provider' => env('PAYMENT_PROVIDER', 'asaas'),
    ],

    'sales' => [
        'default_analyst_email' => env('DEFAULT_ANALYST_EMAIL', 'analista@cpfclean.com.br'),
        'research_commission_rate' => (float) env('RESEARCH_COMMISSION_RATE', 0.30),
        'installment_commission_rate' => (float) env('INSTALLMENT_COMMISSION_RATE', 0.40),
        'commission_hold_hours' => (int) env('COMMISSION_HOLD_HOURS', 24),
    ],

    'asaas' => [
        'base_url' => env('ASAAS_BASE_URL', 'https://sandbox.asaas.com/api/v3'),
        'api_key' => env('ASAAS_API_KEY'),
        'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
    ],

    'apibrasil' => [
        'base_url' => env('APIBRASIL_BASE_URL', 'https://gateway.apibrasil.io'),
        'token' => env('APIBRASIL_TOKEN'),
        'token_header' => env('APIBRASIL_TOKEN_HEADER', 'Authorization'),
        'token_prefix' => env('APIBRASIL_TOKEN_PREFIX', 'Bearer'),
        'balance_path' => env('APIBRASIL_BALANCE_PATH', '/api/v2/user'),
        'balance_method' => env('APIBRASIL_BALANCE_METHOD', 'GET'),
        'cpf_path' => env('APIBRASIL_CPF_PATH', '/api/v2/consulta/cpf/credits'),
        'cnpj_path' => env('APIBRASIL_CNPJ_PATH', '/api/v2/consulta/cnpj/credits'),
        'cpf_method' => env('APIBRASIL_CPF_METHOD', 'POST'),
        'cnpj_method' => env('APIBRASIL_CNPJ_METHOD', 'POST'),
        'timeout' => (int) env('APIBRASIL_TIMEOUT', 20),
    ],

    'cpfclean' => [
        'whatsapp_number' => env('CPFCLEAN_WHATSAPP_NUMBER', '5531998428448'),
    ],

    'zapi' => [
        'instance' => env('ZAPI_INSTANCE'),
        'token' => env('ZAPI_TOKEN'),
        'client_token' => env('ZAPI_CLIENT_TOKEN'),
    ],

];
