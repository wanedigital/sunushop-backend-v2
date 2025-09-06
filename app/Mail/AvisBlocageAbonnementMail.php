<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Abonnement;

class AvisBlocageAbonnementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $abonnement;

    /**
     * Create a new message instance.
     */
    public function __construct(Abonnement $abonnement)
    {
        $this->abonnement = $abonnement;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre abonnement SunuShop a été bloqué.',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.abonnements.avis_blocage',
            with: [
                'abonnement' => $this->abonnement,
                'plan' => $this->abonnement->plan,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
