<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $temporaryPassword,
        public string $markdownContent,
        public ?string $roleName = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vos identifiants d\'accès — Cité Étoile du Monde',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user-credentials',
            with: [
                'user' => $this->user,
                'temporaryPassword' => $this->temporaryPassword,
                'markdownContent' => $this->markdownContent,
                'roleName' => $this->roleName ?? ($this->user->roles->first()?->name ?? 'customer'),
                'loginUrl' => url('/login'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->markdownContent, 'identifiants-acces.md')
                ->withMime('text/markdown'),
        ];
    }
}
