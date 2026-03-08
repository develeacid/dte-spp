<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitacionUsuario extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $activationUrl,
        public string $roleName,
        public string $urName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación al Sistema de Planeación y Programación',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitacion-usuario',
        );
    }
}
