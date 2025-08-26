<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\DetailCommande;
use App\Models\Produit;
use App\Models\Boutique;
use App\Models\Paiement;
use App\Models\TypePaiement;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmationCommande;

class CommandeController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
               // $user = Auth::guard('sanctum')->user();

            
            if (!$user) {
                return response()->json([
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $commandes = Commande::with(['detailCommandes.produit', 'paiement'])
                ->where('id_user', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $commandes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        //$user = Auth::user();
        $user = Auth::guard('sanctum')->user();

        // Règles de validation de base
        $rules = [
            'items' => 'required|array|min:1',
            'items.*.produitId' => 'required|exists:produits,id',
            'items.*.quantite' => 'required|integer|min:1',
            'adresseLivraison' => 'required|string|max:500',
            'telephone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ];

        // Ajouter les règles pour les clients non connectés seulement
        if (!$user) {
            $rules['nom'] = 'required|string|max:100';
            $rules['prenom'] = 'required|string|max:100';
            $rules['email'] = 'required|email|max:255';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Trouver le type de paiement "Paiement à la livraison"
            $typePaiementLivraison = TypePaiement::where('libelle', 'Paiement à la livraison')->first();

            if (!$typePaiementLivraison) {
                // Si le type de paiement n'existe pas, la transaction ne peut pas continuer
                throw new Exception("Le type de paiement 'Paiement à la livraison' est introuvable. Veuillez initialiser les données de la base.");
            }

            $total = 0;
            $produits = []; // Pour garder les références aux produits

            // 1. Vérification des quantités
            foreach ($request->items as $item) {
                $produit = Produit::findOrFail($item['produitId']);
                $produits[$item['produitId']] = $produit; // Stocker la référence
                
                // Vérifier la quantité disponible
                if ($produit->quantite < $item['quantite']) {
                    throw new Exception("Quantité insuffisante pour: " . $produit->libelle);
                }
                
                $total += $produit->prix * $item['quantite'];
            }

            // Créer la commande
            $commandeData = [
                'total' => $total,
                'adresse_client' => $request->adresseLivraison,
                'telephone_client' => $request->telephone,
                'notes' => $request->notes
            ];

            // Si l'utilisateur est connecté
            if ($user) {
                $commandeData['id_user'] = $user->id;
            } else {
                // Client non connecté
                $commandeData['nom_client'] = $request->nom;
                $commandeData['prenom_client'] = $request->prenom;
                $commandeData['email_client'] = $request->email;
            }

            $commande = Commande::create($commandeData);

            // Créer l'enregistrement de paiement associé
            Paiement::create([
                'commande_id' => $commande->id,
                'type_paiement_id' => $typePaiementLivraison->id,
                'montantTotal' => $commande->total,
                'status' => 'en attente', // Statut initial
                'date' => now()
            ]);

            // 2. Création des détails + décrémentation
            foreach ($request->items as $item) {
                $produit = $produits[$item['produitId']]; // Récupérer le produit
                
                DetailCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $produit->id,
                    'quantite' => $item['quantite'],
                    'prixunitaire' => $produit->prix
                ]);

                // Décrémenter la quantité
                $produit->decrement('quantite', $item['quantite']);
            }

            DB::commit();

            

            // Charger les relations pour la réponse
            $commande->load(['detailCommandes.produit', 'paiement']);

             // Envoyer l'email si invité
            if (!$user) {
                Mail::to($request->email)->send(new ConfirmationCommande($commande));
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => $commande
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            
            $query = Commande::with(['detailCommandes.produit', 'paiement']);
            
            // Si l'utilisateur est connecté, filtrer par ses commandes
            if ($user) {
                $query->where('id_user', $user->id);
            }
            
            $commande = $query->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $commande
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function annuler($id)
    {
        try {
            $user = Auth::user();
            
            $query = Commande::query();
            
            // Si l'utilisateur est connecté, filtrer par ses commandes
            if ($user) {
                $query->where('id_user', $user->id);
            }
            
            $commande = $query->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable'
                ], 404);
            }

            if (!$commande->peutEtreAnnulee()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut plus être annulée'
                ], 400);
            }

            $commande->update(['etat' => 'annuler']);
             // Réincrémenter les quantités des produits
        foreach ($commande->detailCommandes as $detail) {
            $produit = $detail->produit;
            $produit->increment('quantite', $detail->quantite);
        }

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès',
                'data' => $commande
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Méthode pour les commandes d'invités (par numéro de commande et email)
    public function getCommandeInvite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numeroCommande' => 'required|string',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $commande = Commande::with(['detailCommandes.produit', 'paiement'])
                ->where('numeroCommande', $request->numeroCommande)
                ->where('email_client', $request->email)
                ->whereNull('id_user') // Seulement les commandes d'invités
                ->first();

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $commande
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCommandesByBoutique($id)
    {
        $user = Auth::user();

        // Vérifier si la boutique appartient à ce user
        $boutique = Boutique::where('id', $id)
                    ->where('id_user', $user->id)
                    ->first();

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Cette boutique ne vous appartient pas.'
            ], 403);
        }

        // ... ensuite récupérer les commandes comme avant
        $produitIds = DB::table('produit_boutiques')
            ->where('id_boutique', $id)
            ->pluck('id_produit');

        $commandeIds = DB::table('detail_commandes')
            ->whereIn('produit_id', $produitIds)
            ->distinct()
            ->pluck('commande_id');

        $commandes = Commande::with(['detailCommandes.produit', 'user', 'paiement'])
            ->whereIn('id', $commandeIds)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $commandes
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updateStatut(Request $request, $id)
{
    try {
        $user = Auth::user();
        
        if (!$user || $user->profil->libelle !== 'Vendeur') {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'etat' => 'required|in:en attente,annuler,valider,en cours,terminer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Statut invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        $commande = Commande::find($id);
        
        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande introuvable'
            ], 404);
        }

        // Mettre à jour le statut
        $commande->etat = $request->etat;
        $commande->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => $commande
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du statut',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function updatePaiementStatus(Request $request, $commandeId)
{
    try {
        $user = Auth::user();

        // 1. Validation
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:en attente,reussi,echoue'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
        }

        $commande = Commande::with('paiement')->find($commandeId);

        if (!$commande || !$commande->paiement) {
            return response()->json(['success' => false, 'message' => 'Paiement de commande introuvable'], 404);
        }

        // 2. Autorisation (simplifiée pour le MVP : Vendeur ou Admin)
        // Une logique plus fine vérifierait que la commande contient des produits du vendeur.
        if ($user->profil->libelle !== 'Vendeur' && $user->profil->libelle !== 'Admin') {
             return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }
        
        // TODO: Pour une V2, vérifier que la commande appartient bien au vendeur.

        // 3. Mise à jour
        $paiement = $commande->paiement;
        $paiement->status = $request->status;
        $paiement->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut du paiement mis à jour.',
            'data' => $paiement
        ]);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du paiement.',
            'error' => $e->getMessage()
        ], 500);
    }
}

/*public function ventesVendeurParPeriode($type)
{
    $user = Auth::user();

    if (!$user || $user->profil->libelle !== 'Vendeur') {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé'
        ], 403);
    }

    $query = DB::table('commandes')
        ->join('detail_commandes', 'commandes.id', '=', 'detail_commandes.commande_id')
        ->join('produits', 'detail_commandes.produit_id', '=', 'produits.id')
        ->join('produit_boutiques', 'produits.id', '=', 'produit_boutiques.id_produit')
        ->join('boutiques', 'produit_boutiques.id_boutique', '=', 'boutiques.id')
        ->where('boutiques.id_user', $user->id);

    if ($type === 'mois') {
        $query->whereRaw('EXTRACT(YEAR FROM commandes.date) = ?', [now()->year])
            ->selectRaw('                EXTRACT(MONTH FROM commandes.date) as periode,
                SUM(detail_commandes.quantite * detail_commandes.prixunitaire) as total_ventes,
                COUNT(DISTINCT commandes.id) as nombre_commandes
            ')
            ->groupBy(DB::raw('EXTRACT(MONTH FROM commandes.date)'))
            ->orderBy('periode');
    } elseif ($type === 'annee') {
        $query->selectRaw('                EXTRACT(YEAR FROM commandes.date) as periode,
                SUM(detail_commandes.quantite * detail_commandes.prixunitaire) as total_ventes,
                COUNT(DISTINCT commandes.id) as nombre_commandes
            ')
            ->groupBy(DB::raw('EXTRACT(YEAR FROM commandes.date)'))
            ->orderBy('periode');
    } else {
        return response()->json([
            'success' => false,
            'message' => 'Type de période invalide. Utilisez "mois" ou "annee".'
        ], 400);
    }

    $stats = $query->get();

    return response()->json([
        'success' => true,
        'data' => $stats
    ]);
}
public function meilleursClientsVendeur()
{
    $user = Auth::user();

    if (!$user || $user->profil->libelle !== 'Vendeur') {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé'
        ], 403);
    }

    $clients = DB::table('commandes')
        ->join('detail_commandes', 'commandes.id', '=', 'detail_commandes.commande_id')
        ->join('produits', 'detail_commandes.produit_id', '=', 'produits.id')
        ->join('produit_boutiques', 'produits.id', '=', 'produit_boutiques.id_produit')
        ->join('boutiques', 'produit_boutiques.id_boutique', '=', 'boutiques.id')
        ->leftJoin('users', 'commandes.id_user', '=', 'users.id') // client connecté
        ->where('boutiques.id_user', $user->id)
        ->selectRaw("            COALESCE(commandes.nom_client, users.nom) as nom,
            COALESCE(commandes.prenom_client, '') as prenom,
            COALESCE(commandes.email_client, users.email) as email,
            COUNT(DISTINCT commandes.id) as nombre_commandes,
            SUM(detail_commandes.quantite * detail_commandes.prixunitaire) as total_depense
        ")
        ->groupBy(
    DB::raw('COALESCE(commandes.nom_client, users.nom)'),
    DB::raw('COALESCE(commandes.prenom_client, "")'),
    DB::raw('COALESCE(commandes.email_client, users.email)')
)
        ->orderByDesc('total_depense')
        ->limit(10)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $clients
    ]);
}*/


}
