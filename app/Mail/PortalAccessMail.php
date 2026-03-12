<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalAccessMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public Order $order,
        public string $accessLink,
        public string $temporaryPassword
    ) {
    }

    public function envelope(): Envelope
    {
        $protocol = $this->order->protocolo ?: 'CPF Clean';

        return new Envelope(
            subject: "Acesso à área do cliente CPF Clean - {$protocol}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-access'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
