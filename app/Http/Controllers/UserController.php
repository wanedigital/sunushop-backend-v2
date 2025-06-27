<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Récupère la liste de tous les utilisateurs.
     * Accessible uniquement aux administrateurs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Récupère tous les utilisateurs avec leurs profils
        $users = User::with('profil')->get();

        return response()->json(['users' => $users], 200);
    }

    /**
     * Récupère les détails d'un utilisateur spécifique.
     * Accessible uniquement aux administrateurs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = User::with('profil')->find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        return response()->json(['user' => $user], 200);
    }

    /**
     * Met à jour les informations d'un utilisateur.
     * Accessible uniquement aux administrateurs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'profil_id' => 'required|exists:profils,id',
            'status' => 'required|in:actif,inactif',
        ]);

        $user->update([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'adresse' => $validated['adresse'],
            'telephone' => $validated['telephone'],
            'email' => $validated['email'],
            'password' => isset($validated['password']) ? Hash::make($validated['password']) : $user->password,
            'profil_id' => $validated['profil_id'],
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user->load('profil'),
        ], 200);
    }

    /**
     * Désactive un utilisateur (soft delete ou mise à jour du statut).
     * Accessible uniquement aux administrateurs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Mise à jour du statut au lieu de suppression physique
        $user->update(['status' => 'inactif']);

        return response()->json(['message' => 'Utilisateur désactivé avec succès'], 200);
    }

    /**
     * Récupère la liste des rôles (profils).
     * Accessible uniquement aux administrateurs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function roles(Request $request)
    {
        $roles = Profil::all();

        return response()->json(['roles' => $roles], 200);
    }
}