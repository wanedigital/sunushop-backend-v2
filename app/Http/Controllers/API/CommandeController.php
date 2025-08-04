<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\DetailCommande;
use App\Models\Produit;
use App\Models\Boutique;

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

            $commandes = Commande::with(['detailCommandes.produit'])
                ->where('id_user', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $commandes->map(function ($commande) {
                    return [
                        'id' => $commande->id,
                        'numeroCommande' => $commande->numeroCommande,
                        'date' => $commande->date,
                        'etat' => $commande->etat,
                        'total' => $commande->total,
                        'adresse' => $commande->adresse_client,
                        'telephone' => $commande->telephone_client,
                        'notes' => $commande->notes,
                        'items' => $commande->detailCommandes->map(function ($detail) {
                            return [
                                'produit' => $detail->produit,
                                'quantite' => $detail->quantite,
                                'prixUnitaire' => $detail->prixunitaire
                            ];
                        })
                    ];
                })
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
            $total = 0;
            $produits = []; // Pour garder les références aux produits

            // 1. Vérification des quantités
            foreach ($request->items as $item) {
                $produit = Produit::findOrFail($item['produitId']);
                $produits[$item['produitId']] = $produit; // Stocker la référence
                
                // Vérifier la quantité disponible
                if ($produit->quantite < $item['quantite']) {
                    throw new \Exception("Quantité insuffisante pour: " . $produit->libelle);
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
            $commande->load(['detailCommandes.produit']);

             // Envoyer l'email si invité
            if (!$user) {
                Mail::to($request->email)->send(new ConfirmationCommande($commande));
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => [
                    'id' => $commande->id,
                    'numeroCommande' => $commande->numeroCommande,
                    'date' => $commande->date,
                    'etat' => $commande->etat,
                    'total' => $commande->total,
                    'adresse' => $commande->adresse_client,
                    'telephone' => $commande->telephone_client,
                    'notes' => $commande->notes,
                    'nomComplet' => $commande->nom_complet_client,
                    'items' => $commande->detailCommandes->map(function ($detail) {
                        return [
                            'produit' => $detail->produit,
                            'quantite' => $detail->quantite,
                            'prixUnitaire' => $detail->prixunitaire
                        ];
                    })
                ]
            ], 201);

        } catch (\Exception $e) {
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
            
            $query = Commande::with(['detailCommandes.produit']);
            
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
                'data' => [
                    'id' => $commande->id,
                    'numeroCommande' => $commande->numeroCommande,
                    'date' => $commande->date,
                    'etat' => $commande->etat,
                    'total' => $commande->total,
                    'adresse' => $commande->adresse_client,
                    'telephone' => $commande->telephone_client,
                    'email' => $commande->email_client,
                    'notes' => $commande->notes,
                    'nomComplet' => $commande->nom_complet_client,
                    'items' => $commande->detailCommandes->map(function ($detail) {
                        return [
                            'produit' => $detail->produit,
                            'quantite' => $detail->quantite,
                            'prixUnitaire' => $detail->prixunitaire
                        ];
                    })
                ]
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

            // Remettre les produits en stock si géré
            /*foreach ($commande->detailCommandes as $detail) {
                if (isset($detail->produit->stock)) {
                    $detail->produit->increment('quantite', $detail->quantite);
                }
            }*/

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
            $commande = Commande::with(['detailCommandes.produit'])
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
                'data' => [
                    'id' => $commande->id,
                    'numeroCommande' => $commande->numeroCommande,
                    'date' => $commande->date,
                    'etat' => $commande->etat,
                    'total' => $commande->total,
                    'adresse' => $commande->adresse_client,
                    'telephone' => $commande->telephone_client,
                    'email' => $commande->email_client,
                    'notes' => $commande->notes,
                    'nomComplet' => $commande->nom_complet_client,
                    'items' => $commande->detailCommandes->map(function ($detail) {
                        return [
                            'produit' => $detail->produit,
                            'quantite' => $detail->quantite,
                            'prixUnitaire' => $detail->prixunitaire
                        ];
                    })
                ]
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

        $commandes = Commande::with(['detailCommandes.produit', 'user'])
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
}
