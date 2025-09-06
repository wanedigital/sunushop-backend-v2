<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VerifierAbonnement
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$fonctionnalites)
    {
        $user = Auth::user();

        // Laisser passer les admins, ils ont tous les droits
        if ($user->profil->libelle === 'Administrateur') {
            return $next($request);
        }

                // Récupérer l'abonnement actif ou en période de grâce de l'utilisateur
        $abonnement = $user->abonnements()
                          ->whereIn('statut', ['actif', 'en_grace'])
                          ->where('date_fin', '>', Carbon::now()->subDays(7)) // Inclut la période de grâce
                          ->latest()->first();

        if (!$abonnement) {
            return response()->json(['success' => false, 'message' => 'Aucun abonnement actif trouvé. Veuillez souscrire à un plan.', 'code' => 'NO_ACTIVE_SUBSCRIPTION'], 403);
        }

        // --- Vérification des fonctionnalités ---
        $plan = $abonnement->plan;

        // --- Début de la solution de contournement pour le cast JSON ---
        $fonctionnalitesArray = $plan->fonctionnalites;
        if (is_string($fonctionnalitesArray)) {
            $fonctionnalitesArray = json_decode($fonctionnalitesArray, true);
        }
        // --- Fin de la solution de contournement ---

        $limiteProduits = $fonctionnalitesArray['max_produits'] ?? 0;

        // Si la limite est -1, c est illimité, on laisse passer.
        if ($limiteProduits === -1) {
            return $next($request);
        }

        // Compter les produits actuels du vendeur
        // Note: Assure que la relation 'boutique.produits' est bien définie sur le modèle User
        $nombreProduitsActuels = $user->boutique ? $user->boutique->produits()->count() : 0;

        if ($nombreProduitsActuels >= $limiteProduits) {
            return response()->json([
                'success' => false, 
                'message' => 'Limite de produits atteinte pour votre plan d abonnement. Veuillez passer à un plan supérieur.'
            ], 403); // 403 Forbidden est plus approprié ici
        }

        return $next($request);
    }
}
