<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Boutique;
use Illuminate\Support\Facades\Storage;

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
        'status' => 'required|string',
        'id_user' => 'required|integer',
    ]);

    $data = $request->all();

    if ($request->hasFile('logo')) {
        $path = $request->file('logo')->store('logos', 'public');
        $data['logo'] = '/storage/' . $path;
    }

    $boutique = Boutique::create($data);

    return response()->json($boutique);
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

    
    public function produits($id)
    {
        $boutique = Boutique::findOrFail($id);

        return response()->json([
            'boutique' => $boutique->nom,
            'produits' => $boutique->produits
        ]);
    }

    
}
