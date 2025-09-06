<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Abonnement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\AvisExpirationAbonnementMail;
use App\Mail\AvisBlocageAbonnementMail;
use App\Mail\AvisRappelGraceMail;
use App\Mail\AvisRenouvellementProactifMail;

class VerifierAbonnements extends Command
{
    protected $signature = 'abonnements:verifier';
    protected $description = 'Vérifie les abonnements pour gérer les expirations et les périodes de grâce';

    public function handle()
    {
        $this->info('Début de la vérification des abonnements...');

        // --- Cas 0: Envoyer un rappel proactif avant l'expiration (ex: 7 jours avant) ---
        $joursAvantExpiration = 7;
        $abonnementsProactifs = Abonnement::where('statut', 'actif')
                                        ->where('date_fin', '>', Carbon::now())
                                        ->where('date_fin', '<=', Carbon::now()->addDays($joursAvantExpiration))
                                        // S'assurer que l'email n'est envoyé qu'une seule fois
                                        ->where(function ($query) use ($joursAvantExpiration) {
                                            $query->whereNull('date_dernier_rappel_proactif')
                                                  ->orWhere('date_dernier_rappel_proactif', '<=', Carbon::now()->subDays($joursAvantExpiration));
                                        })
                                        ->get();

        foreach ($abonnementsProactifs as $abonnement) {
            $this->info("Envoi du rappel proactif pour l'abonnement #{$abonnement->id}.");
            Mail::to($abonnement->user->email)->send(new AvisRenouvellementProactifMail($abonnement));
            $abonnement->date_dernier_rappel_proactif = Carbon::now();
            $abonnement->save();
        }

        // --- Cas 1: Gérer les abonnements actifs qui viennent d'expirer (entrée en période de grâce) ---
        $abonnementsActifsExpires = Abonnement::where('statut', 'actif')
                                            ->where('date_fin', '<', Carbon::now())
                                            ->get();

        foreach ($abonnementsActifsExpires as $abonnement) {
            $abonnement->statut = 'en_grace';
            $abonnement->save();
            $this->warn("L'abonnement #{$abonnement->id} est entré en période de grâce.");
            Mail::to($abonnement->user->email)->send(new AvisExpirationAbonnementMail($abonnement));
        }

        // --- Cas 2: Envoyer un rappel à J+3 de la période de grâce ---
        $rappelGraceJ3 = 3; // Rappel à 3 jours après le début de la grâce
        $abonnementsRappelJ3 = Abonnement::where('statut', 'en_grace')
                                        ->where('updated_at', '<', Carbon::now()->subDays($rappelGraceJ3))
                                        ->where('updated_at', '>', Carbon::now()->subDays($rappelGraceJ3 + 1)) // S'assurer qu'il n'est envoyé qu'une fois
                                        ->get();

        foreach ($abonnementsRappelJ3 as $abonnement) {
            $this->info("Envoi du rappel J+3 pour l'abonnement #{$abonnement->id}.");
            Mail::to($abonnement->user->email)->send(new AvisRappelGraceMail($abonnement));
        }

        // --- Cas 3: Gérer les abonnements dont la période de grâce est terminée (Jour 7) ---
        $delaiGrace = 7; // Période de grâce de 7 jours
        $abonnementsGraceTerminee = Abonnement::where('statut', 'en_grace')
                                             ->where('updated_at', '<', Carbon::now()->subDays($delaiGrace))
                                             ->get();

        foreach ($abonnementsGraceTerminee as $abonnement) {
            $abonnement->statut = 'expire';
            $abonnement->save();
            $this->error("L'abonnement #{$abonnement->id} a expiré après la période de grâce.");
            Mail::to($abonnement->user->email)->send(new AvisBlocageAbonnementMail($abonnement));
        }

        // On pourrait ajouter une 3ème étape ici pour envoyer des rappels à J+3

        $this->info('Vérification terminée.');
        return 0;
    }
}
