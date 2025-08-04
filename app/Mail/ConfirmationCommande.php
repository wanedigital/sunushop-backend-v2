<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Commande;


class ConfirmationCommande extends Mailable
{
    use Queueable, SerializesModels;

    public $commande;


    /**
     * Create a new message instance.
     */

    public function __construct(Commande $commande)
    {
        $this->commande = $commande;
    }

    public function build()
    {
        return $this->subject('Confirmation de votre commande #'.$this->commande->numeroCommande)
                    ->view('emails.confirmation-commande');
    }

    
}
