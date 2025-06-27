<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProduitBoutique;

class Prod_BoutiqueController extends Controller
{
    // 🔹 Récupérer tous les produits dans les boutiques
    public function index()
    {
        return response()->json(ProduitBoutique::all(), 200);
    }

    // 🔹 Ajouter un produit à une boutique
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_produit' => 'required|exists:produits,id',
            'id_boutique' => 'required|exists:boutiques,id',
        ]);

        $produitBoutique = ProduitBoutique::create($validatedData);

        return response()->json(['message' => 'Produit ajouté à la boutique avec succès', 'data' => $produitBoutique], 201);
    }

    // 🔹 Récupérer un produit précis dans une boutique
    public function show($id)
    {
        $produitBoutique = ProduitBoutique::findOrFail($id);
        return response()->json($produitBoutique, 200);
    }

    // 🔹 Mettre à jour l'association entre un produit et une boutique
    public function update(Request $request, $id)
    {
        $produitBoutique = ProduitBoutique::findOrFail($id);

        $validatedData = $request->validate([
            'id_produit' => 'required|exists:produits,id',
            'id_boutique' => 'required|exists:boutiques,id',
        ]);

        $produitBoutique->update($validatedData);

        return response()->json(['message' => 'ProduitBoutique mis à jour avec succès', 'data' => $produitBoutique], 200);
    }

    // 🔹 Supprimer un produit d'une boutique
    public function destroy($id)
    {
        ProduitBoutique::destroy($id);
        return response()->json(['message' => 'Produit supprimé de la boutique'], 204);
    }
}
