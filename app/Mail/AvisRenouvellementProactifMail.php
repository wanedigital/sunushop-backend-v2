<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Abonnement;

class AvisRenouvellementProactifMail extends Mailable
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
            subject: 'Votre abonnement SunuShop arrive à échéance bientôt !',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.abonnements.avis_renouvellement_proactif',
            with: [
                'abonnement' => $this->abonnement,
                'plan' => $this->abonnement->plan,
                'dateFin' => $this->abonnement->date_fin->format('d/m/Y'),
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
