<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Boutique;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\DemandeValidationProfil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Categorie;
use App\Models\Produit;
use App\Models\Plan;
use App\Models\Abonnement;
use Carbon\Carbon;

class BoutiqueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        
        $search = $request->query('search'); // Récupération du terme de recherche
        $query = Boutique::with('user');     

       if ($search) {
           $query->where('nom', 'LIKE', "%$search%")
                 ->orWhere('adresse', 'LIKE', "%$search%")
                 ->orWhere('numeroCommercial', 'LIKE', "%$search%")
                 ->orWhere('status', 'LIKE', "%$search%");
        }

        $boutiques = $query->get();

        return response()->json($boutiques);
        
    }
     public function allboutique()
    {
        return response()->json(Boutique::all(), 200);
    }

    //assister par b'one il affiche toutes les boutiques 
    // si c'est l'admin qui esr connectter et si 
    // c'est le vendeur l'ensemble de ces boutique
    /*public function index(Request $request)
    {
        $user = $request->user();

        // Assure-toi que le profil est bien chargé (avec la relation)
        $user->load('profil');

        if ($user->profil->libelle === 'Administrateur') {
            $boutiques = Boutique::with('user')->get(); // Toutes les boutiques
        } elseif ($user->profil->libelle === 'Vendeur') {
            $boutiques = Boutique::with('user')
                        ->where('id_user', $user->id)
                        ->get(); // Boutiques du vendeur
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $boutiques
        ]);
    }*/


    /**
     * Store a newly created resource in storage.
     */

public function store(Request $request)
{
    $request->validate([
    'nom' => 'required|string',
    'adresse' => 'required|string',
    'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    'numeroCommercial' => 'required|string',
    'status' => 'nullable|in:ouvret,fermer', 
 ]);

$data = $request->only(['nom', 'adresse', 'numeroCommercial', 'status']);
$data['id_user'] = $request->user()->id;

if ($request->hasFile('logo')) {
    $path = $request->file('logo')->store('logos', 'public');
    $data['logo'] = '/storage/' . $path;
}

    $boutique = Boutique::create($data);

    // --- Logique d'abonnement d'essai ---
    // 1. Trouver le plan "Standard".
    $planStandard = Plan::where('nom', 'Standard')->first();

    // 2. Si le plan Standard existe, créer un abonnement d'essai de 30 jours.
    if ($planStandard) {
        Abonnement::create([
            'user_id' => $request->user()->id,
            'plan_id' => $planStandard->id,
            'date_debut' => Carbon::now(),
            'date_fin' => Carbon::now()->addDays(14),
            'statut' => 'actif',
            'statut_paiement' => 'essai' // Statut spécifique pour la période d'essai
        ]);
    }
    // --- Fin de la logique d'abonnement ---

    // Génération du lien signé pour validation
    $validationUrl = URL::temporarySignedRoute(
        'api.boutique.approuver-vendeur',
        now()->addMinutes(60),
        ['user' => auth()->id()]
    );

    // Envoi de l’email avec le lien
    Mail::to(auth()->user()->email)->send(new DemandeValidationProfil($validationUrl));

    return response()->json([
        'message' => 'Boutique créée avec succès. Un email vous a été envoyé pour activer votre profil de vendeur.',
        'boutique' => $boutique,
    ]);

}



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        return Boutique::with('produits')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
        public function update(Request $request, $id)
        {
            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'adresse' => 'required|string|max:255',
                'numeroCommercial' => 'nullable|string|max:20',
                'status' => 'required|in:ouvert,fermer',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $boutique = Boutique::findOrFail($id);

            // Gestion du logo
            if ($request->hasFile('logo')) {
                // Supprimer l'ancien logo si existe
                if ($boutique->logo) {
                    Storage::delete(str_replace('storage/', 'public/', $boutique->logo));
                }
                
                $path = $request->file('logo')->store('public/logos');
                $validated['logo'] = str_replace('public/', 'storage/', $path);
            }

            $boutique->update($validated);

            return response()->json([
                'message' => 'Boutique mise à jour avec succès',
                'boutique' => $boutique
            ]);
        }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $boutique = Boutique::findOrFail($id);

        $boutique->delete();

        return response()->json(['message' => 'Boutique supprimée']);
    }

        public function mesProduits()
        {
            $user = Auth::user();

            if ($user->profil->libelle !== 'Vendeur') {
                return response()->json(['message' => 'Accès non autorisé'], 403);
            }

            // Récupère la boutique liée au vendeur
            $boutique = Boutique::with('produits')
                ->where('id_user', $user->id)
                ->firstOrFail();

            return response()->json([
        'id' => $boutique->id,
        'nom' => $boutique->nom,
        'adresse' => $boutique->adresse,
        'numeroCommercial' => $boutique->numeroCommercial,
        'status' => $boutique->status,
        'logo' => $boutique->logo ? asset($boutique->logo) : null,
                'produits' => $boutique->produits->map(function ($produit) {
                    return [
                        'id' => $produit->id,
                        'libelle' => $produit->libelle,
                        'description' => $produit->description,
                        'prix' => $produit->prix,
                        'quantite' => $produit->quantite,
                        'image' => $produit->image ? asset($produit->image) : null,
                        'disponible' => $produit->disponible,
                        'categorie_id' => $produit->categorie_id,
                        'created_at' => $produit->created_at,
                        'updated_at' => $produit->updated_at
                    ];
                })
            ]);
        }

    // BoutiqueController.php
    public function getCategories($boutiqueId)
    {
        $categories = Categorie::whereHas('produits', function ($query) use ($boutiqueId) {
            $query->whereHas('boutiques', function ($q) use ($boutiqueId) {
                $q->where('boutiques.id', $boutiqueId);
            });
        })->get();

        return response()->json($categories);
    }

    public function search($boutiqueId, Request $request)
    {
        $searchTerm = $request->query('q');
        
        $produits = Produit::whereHas('boutiques', function ($query) use ($boutiqueId) {
            $query->where('boutiques.id', $boutiqueId);
        })
        ->where(function ($query) use ($searchTerm) {
            $query->where('libelle', 'LIKE', "%$searchTerm%")
                ->orWhere('description', 'LIKE', "%$searchTerm%");
        })
        ->get();

        return response()->json($produits);
    }

    public function produits($id)
    {
        $boutique = Boutique::with('produits')->findOrFail($id);
        
        return response()->json([
            'boutique' => $boutique->nom,
            'boutique_image' => $boutique->logo ? asset('storage/' . $boutique->logo) : null,
            'produits' => $boutique->produits->map(function($produit) {
                return [
                    'id' => $produit->id,
                    'libelle' => $produit->libelle,
                    'description' => $produit->description,
                    'prix' => $produit->prix,
                    'quantite' => $produit->quantite,
                    'image' => $produit->image ? asset( $produit->image) : null,
                    'disponible' => $produit->disponible,
                    'categorie_id' => $produit->categorie_id,
                    'created_at' => $produit->created_at,
                    'updated_at' => $produit->updated_at
                ];
            })
        ]);
    }
}
