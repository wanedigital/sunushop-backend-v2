<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
    $page = $request->input('page', 1);
    
    $query = Produit::query();
    $total = $query->count();
    
    $produits = $query->skip(($page - 1) * $perPage)
                     ->take($perPage)
                     ->get();
    
    return response()->json([
        'data' => $produits,
        'total' => $total
    ]);
        
        $search = $request->query('search'); // Récupération du terme de recherche
        $query = Produit::with('categorie');     

       if ($search) {
           $query->where('libelle', 'LIKE', "%$search%");
        }

        $produits = $query->get();

        return response()->json($produits);
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    // 1Validation
    $request->validate([
        'libelle' => 'required|string|max:255',
        'description' => 'nullable|string',
        'prix' => 'required|numeric',
        'quantite' => 'required|numeric',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'disponible' => 'required|boolean',
        'categorie_id' => 'required|exists:categories,id'
    ]);

    // Récupération de la boutique du vendeur
    $vendeur = Auth::user();
    $boutique = $vendeur->boutique; // Relation One-to-One dans ton modèle User
    if (!$boutique) {
        return response()->json(['message' => 'Boutique introuvable pour ce vendeur'], 404);
    }

    //  Gestion image
    $imagePath = $request->file('image')
        ? $request->file('image')->store('produits', 'public')
        : null;
    \Log::info('Image path: ' . $imagePath);
    //  Création du produit
    $produit = new Produit();
    $produit->libelle = $request->input('libelle');
    $produit->description = $request->input('description');
    $produit->prix = $request->input('prix');
    $produit->quantite = $request->input('quantite');
    $produit->image = $imagePath;
    $produit->disponible = $request->input('disponible');
    $produit->categorie_id = $request->input('categorie_id');
    $produit->save();

    //  Insertion dans la table pivot
    DB::table('produit_boutiques')->insert([
        'id_produit' => $produit->id,
        'id_boutique' => $boutique->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    //  Réponse
    return response()->json([
        'message' => 'Produit ajouté avec succès',
        'produit' => $produit
    ], 201);
}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Produit::findOrFail($id);

    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, $id)
{
    // Trouver le produit ou renvoyer une erreur 404
    $produit = Produit::findOrFail($id);

    // Validation des données envoyées
    $validatedData = $request->validate([
        'libelle' => 'required|string|max:255',
        'description' => 'nullable|string',
        'prix' => 'required|numeric',
        'quantite' => 'required|numeric',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'disponible' => 'required|boolean',
        'categorie_id' => 'required|exists:categories,id'
    ]);

    // Gestion de l'image : mise à jour uniquement si une nouvelle est envoyée
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('produits', 'public');
        $validatedData['image'] = $imagePath;
    }

    // Mise à jour du produit
    $produit->update($validatedData);

    return response()->json([
        'message' => 'Produit mis à jour avec succès',
        'produit' => $produit
    ], 200);
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Produit::destroy($id);
        return response()->json(null, 204);
    }

    
}
