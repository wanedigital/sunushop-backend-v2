<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbonnementController extends Controller
{
    // Méthodes pour les vendeurs

    /**
     * Affiche la liste de tous les plans disponibles.
     */
    public function listerPlansDisponibles()
    {
        $plans = Plan::where('prix', '>', 0)->get(); // On n'affiche pas le plan gratuit
        return response()->json(['success' => true, 'data' => $plans]);
    }

    /**
     * Affiche l'abonnement actuel de l'utilisateur connecté.
     */
    public function voirMonAbonnement()
    {
        $user = Auth::user();
        $abonnement = $user->abonnements()->with('plan')->latest()->first();

        if (!$abonnement) {
            return response()->json(['success' => false, 'message' => 'Aucun abonnement trouvé.'], 404);
        }

        return response()->json(['success' => true, 'data' => $abonnement]);
    }

    // Les méthodes pour initier et annuler un abonnement seront plus complexes
    // et dépendront du système de paiement. On les laisse en attente pour le MVP.


    // Méthodes pour les administrateurs

    /**
     * Affiche tous les abonnements de la plateforme (Admin).
     */
    public function index()
    {
        $this->authorize('viewAny', Abonnement::class); // Exemple de politique de sécurité
        $abonnements = Abonnement::with(['user', 'plan'])->latest()->paginate(20);
        return response()->json(['success' => true, 'data' => $abonnements]);
    }

    /**
     * Affiche un abonnement spécifique (Admin).
     */
    public function show(Abonnement $abonnement)
    {
        $this->authorize('view', $abonnement);
        $abonnement->load(['user', 'plan']);
        return response()->json(['success' => true, 'data' => $abonnement]);
    }

    // La création manuelle et la mise à jour par l'admin peuvent être ajoutées ici au besoin.

}
