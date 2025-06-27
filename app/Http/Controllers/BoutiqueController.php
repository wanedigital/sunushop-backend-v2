<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Boutique;

class BoutiqueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return Boutique::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'adresse' => 'required|string',
            'logo' => 'required|string',
            'numeroCommercial' => 'nullable|string',
            'status' => 'in:ouvret,fermer',
            'id_user' => 'required|exists:users,id',
        ]);

        $boutique = Boutique::create($request->all());

        return response()->json($boutique, 201);
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
        $this->authorize('delete', $boutique);
        $boutique->delete();

        return response()->json(['message' => 'Boutique supprimée']);
    }

    // public function produits($id) {
    //     $boutique = Boutique::with('produits')->findOrFail($id);
    //     return $boutique->produits;
    // }

    
    public function produits($id)
    {
        $boutique = Boutique::findOrFail($id);

        return response()->json([
            'boutique' => $boutique->nom,
            'produits' => $boutique->produits
        ]);
    }

    
}
