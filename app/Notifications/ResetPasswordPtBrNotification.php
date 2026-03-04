<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordPtBrNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $expireMinutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Redefinição de senha - CPF Clean Brasil')
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta.')
            ->action('Redefinir senha', $resetUrl)
            ->line("Este link expira em {$expireMinutes} minutos.")
            ->line('Se você não solicitou a redefinição de senha, pode ignorar este e-mail.')
            ->salutation('Atenciosamente, Equipe CPF Clean Brasil');
    }
}
