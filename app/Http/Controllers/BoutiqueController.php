<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Boutique;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\DemandeValidationProfil;
use App\Models\Categorie;
use App\Models\Produit;

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
    /*$request->validate([
        'nom' => 'required|string',
        'adresse' => 'required|string',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'numeroCommercial' => 'required|string',
        'status' => 'nullable|in:ouvret,fermer', // facultatif
        //'id_user' => 'required|integer',
    ]);

    $data = $request->all();

    if ($request->hasFile('logo')) {
        $path = $request->file('logo')->store('logos', 'public');
        $data['logo'] = '/storage/' . $path;
    }

    $boutique = Boutique::create($data);

    return response()->json($boutique);*/

    //

    $request->validate([
    'nom' => 'required|string',
    'adresse' => 'required|string',
    'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    'numeroCommercial' => 'required|string',
    'status' => 'in:en_attente,actif,suspendu'
 ]);

$data = $request->only(['nom', 'adresse', 'numeroCommercial', 'status']);
$data['id_user'] = $request->user()->id;

if ($request->hasFile('logo')) {
    $path = $request->file('logo')->store('logos', 'public');
    $data['logo'] = '/storage/' . $path;
}

$boutique = Boutique::create($data);
//return response()->json($boutique, 201);

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
    public function update(Request $request, string $id)
    {
        //
        $boutique = Boutique::findOrFail($id);
        //$this->authorize('update', $boutique);

        $boutique->update($request->all());
        return $boutique;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $boutique = Boutique::findOrFail($id);
        //$this->authorize('delete', $boutique); à réutiliser une fois que l'authentification a été gérée (Policy)
        $boutique->delete();

        return response()->json(['message' => 'Boutique supprimée']);
    }

    // public function produits($id) {
    //     $boutique = Boutique::with('produits')->findOrFail($id);
    //     return $boutique->produits;
    // }

    
    /*public function produits($id)
    {
        $boutique = Boutique::findOrFail($id);

        return response()->json([
            'boutique' => $boutique->nom,
            'produits' => $boutique->produits
        ]);
    }*/

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
                    'image' => $produit->image ? asset('storage/' . $produit->image) : null,
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

    
}
