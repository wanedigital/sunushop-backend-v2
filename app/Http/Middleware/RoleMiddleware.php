<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Gère une requête entrante et vérifie si l'utilisateur a le rôle requis.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role  Le rôle requis (ex: 'Administrateur', 'Client', 'Vendeur')
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Vérifie si un utilisateur est authentifié
        $user = $request->user();

        if (!$user) {
            \Log::error('Utilisateur non authentifié dans RoleMiddleware', ['request' => $request->all()]);
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        // Vérifie si l'utilisateur a le rôle requis
        \Log::info('Vérification du rôle', ['user_id' => $user->id, 'role_required' => $role]);

        if (!$user->hasRole($role)) {
            return response()->json(['message' => 'Accès non autorisé. Rôle requis : ' . $role], 403);
        }

        return $next($request);
    }
}