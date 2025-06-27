<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Profil;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur.
     * POST /api/register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'profil' => 'required|in:Client,Vendeur',
        ]);

        $profil = Profil::where('libelle', $validated['profil'])->firstOrFail();

        $user = User::create([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'adresse' => $validated['adresse'],
            'telephone' => $validated['telephone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'actif',
            'profil_id' => $profil->id,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Utilisateur inscrit avec succès',
            'user' => $user->load('profil'),
            'token' => $token,
        ], 201);
    }

    /**
     * Connexion d'un utilisateur.
     * POST /api/login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validated)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token', [], $request->remember ? now()->addDays(30) : now()->addHours(2));

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user->load('profil'),
            'token' => $token,
        ]);
    }

    /**
     * Déconnexion de l'utilisateur.
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
        
    }



    /**
     * Récupérer le profil de l'utilisateur connecté.
     * GET /api/user
     */
    public function user(Request $request)
    {
        $user = $request->user()->load('profil');

        return response()->json(['user' => $user]);
    }

    /**
     * Mettre à jour le profil de l'utilisateur connecté.
     * PUT /api/user/update
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'required|string|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();

        $user->update([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'adresse' => $validated['adresse'],
            'telephone' => $validated['telephone'],
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user->load('profil'),
        ]);
    }

    /**
     * Demander un lien de réinitialisation de mot de passe.
     * POST /api/password/forgot
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($validated);

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Lien de réinitialisation envoyé'], 200)
            : response()->json(['message' => 'Erreur lors de l\'envoi du lien'], 400);
    }

    /**
     * Réinitialiser le mot de passe.
     * POST /api/password/reset
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $validated,
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])
                     ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Mot de passe réinitialisé avec succès'], 200)
            : response()->json(['message' => 'Erreur lors de la réinitialisation'], 400);
    }
}