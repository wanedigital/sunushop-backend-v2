<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produit;


class ProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Produit::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         // Validation des données envoyées
    $request->validate([
        'libelle' => 'required|string|max:255',
        'description' => 'nullable|string',
        'prix' => 'required|numeric',
        'quantite' => 'required|numeric',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'disponible' => 'required|boolean',
        'categorie_id' => 'required|exists:categories,id'
    ]);

    // Gestion de l'image (si elle est envoyée)
    $imagePath = $request->file('image') ? $request->file('image')->store('produits', 'public') : null;

    // Création d'un nouveau produit
    $produit = new Produit();
    $produit->libelle = $request->input('libelle');
    $produit->description = $request->input('description');
    $produit->prix = $request->input('prix');
    $produit->quantite = $request->input('quantite');
    $produit->image = $imagePath;
    $produit->disponible = $request->input('disponible');
    $produit->categorie_id = $request->input('categorie_id');
    $produit->save();
    return response()->json(['message' => 'Produit ajouté avec succès', 'produit' => $produit], 201);

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
