<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Plan;
use App\Models\Abonnement;
use Carbon\Carbon;

class InitialiserAbonnementsExistants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abonnements:initialiser-existants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialise les abonnements d\'essai pour les boutiques existantes sans abonnement.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Début de l\'initialisation des abonnements pour les boutiques existantes...');

        // 1. Trouver le plan Standard
        $planStandard = Plan::where('nom', 'Standard')->first();

        if (!$planStandard) {
            $this->error('Le plan Standard est introuvable. Veuillez exécuter php artisan db:seed pour créer les plans.');
            return 1; // Indique une erreur
        }

        // 2. Trouver tous les utilisateurs qui ont une boutique
        $usersAvecBoutique = User::whereHas('boutique')->get();

        $countInitialises = 0;

        foreach ($usersAvecBoutique as $user) {
            // 3. Vérifier si l'utilisateur a déjà un abonnement
            if ($user->abonnements->isEmpty()) {
                // 4. Créer un abonnement d'essai de 30 jours
                Abonnement::create([
                    'user_id' => $user->id,
                    'plan_id' => $planStandard->id,
                    'date_debut' => Carbon::now(),
                    'date_fin' => Carbon::now()->addDays(30),
                    'statut' => 'actif',
                    'statut_paiement' => 'essai'
                ]);
                $countInitialises++;
                $this->comment("Abonnement d'essai créé pour l'utilisateur: {$user->email}");
            }
        }

        $this->info("Initialisation terminée. {$countInitialises} abonnement(s) d'essai créé(s).");
        return 0; // Indique le succès
    }
}
