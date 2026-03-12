<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Acesso à Área do Cliente CPF Clean</title>
</head>
<body style="margin:0;background:#f3f7fa;font-family:Arial,Helvetica,sans-serif;color:#153248;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="background:linear-gradient(135deg,#10354a,#0f6179);border-radius:24px;padding:28px;color:#ffffff;">
            <p style="margin:0;font-size:12px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;opacity:.82;">CPF Clean Brasil</p>
            <h1 style="margin:12px 0 0;font-size:28px;line-height:1.2;">Seu acesso à área do cliente foi liberado</h1>
            <p style="margin:12px 0 0;font-size:15px;line-height:1.7;color:rgba(255,255,255,.88);">
                Olá {{ $user->name ?: 'cliente' }}, o protocolo {{ $order->protocolo ?: '-' }} já tem acesso ativo à área do cliente.
            </p>
        </div>

        <div style="margin-top:20px;background:#ffffff;border:1px solid #d8e4ec;border-radius:24px;padding:24px;">
            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">
                Use os dados abaixo para entrar e acompanhar contrato, histórico e próximos passos:
            </p>

            <div style="border:1px solid #d8e4ec;border-radius:18px;background:#f8fbfd;padding:18px;">
                <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#547084;">Link de acesso</p>
                <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">
                    <a href="{{ $accessLink }}" style="color:#0f6179;font-weight:700;text-decoration:none;">{{ $accessLink }}</a>
                </p>

                <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#547084;">Login</p>
                <p style="margin:0 0 16px;font-size:15px;font-weight:700;">{{ $user->email }}</p>

                <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#547084;">Senha provisória</p>
                <p style="margin:0;font-size:15px;font-weight:700;">{{ $temporaryPassword }}</p>
            </div>

            <p style="margin:18px 0 0;font-size:14px;line-height:1.7;color:#4f6b80;">
                Altere sua senha no primeiro acesso para manter sua conta protegida.
            </p>
        </div>
    </div>
</body>
</html>
