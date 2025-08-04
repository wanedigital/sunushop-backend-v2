<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profil;
use Illuminate\Http\Request;

class ProfilValidationController extends Controller
{
    public function valider(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Lien expiré ou invalide.'], 403);
        }

        $user = User::findOrFail($request->query('user'));
        $vendeurProfil = Profil::where('libelle', 'Vendeur')->firstOrFail();

        // On vérifie s'il est déjà vendeur
        if ($user->profil_id === $vendeurProfil->id) {
            return response()->json(['message' => 'Ce compte est déjà un vendeur.'], 200);
        }

        $user->update(['profil_id' => $vendeurProfil->id]);

        return response()->json(['message' => 'Félicitations ! Votre profil est maintenant Vendeur.'], 200);
    }
}
