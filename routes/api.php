<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\API\ProfilValidationController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\API\StatistiqueController;



use App\Http\Controllers\BoutiqueController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes pour l'authentification, gestion des utilisateurs, et ressources via API.
|
*/


// Routes publiques (authentification)
Route::prefix('')->middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::get('/login', function () {
        return response()->json([
            'message' => 'This endpoint only supports POST requests. Please use POST /api/login to log in.',
        ], 405);
    })->name('api.login.get');
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->name('api.password.forgot');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('api.password.reset');
});

// Routes protégées (auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::put('/user/update', [AuthController::class, 'updateProfile'])->name('api.user.update');
    Route::patch('/user/change-password', [AuthController::class, 'changePassword']);

});

// Routes pour les utilisateurs (administrateurs uniquement)
Route::middleware(['auth:sanctum', 'role:Administrateur'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('api.users.show');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('api.users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('api.users.destroy');
    Route::get('/roles', [UserController::class, 'roles'])->name('api.roles.index');
});




Route::apiResource('produit-boutiques', \App\Http\Controllers\Prod_BoutiqueController::class);


//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
 //   return $request->user();
//});


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('boutiques', \App\Http\Controllers\BoutiqueController::class);
});

Route::get('/allboutiques', [BoutiqueController::class, 'allboutique']);

Route::apiResource('categories', \App\Http\Controllers\CategorieController::class);
Route::apiResource('produits', \App\Http\Controllers\ProduitController::class);

Route::get('/boutiques/{id}/produits', [BoutiqueController::class, 'produits']);

// route qui permet de modifier le produit du client en vendeur lorsqu'il cree une boutique
Route::get('/valider-vendeur', [ProfilValidationController::class, 'valider'])
    ->name('api.boutique.approuver-vendeur');

Route::middleware('auth:sanctum')->post('/produits', [ProduitController::class, 'store']);
Route::middleware('auth:sanctum')->get('/vendeur/produits', [BoutiqueController::class, 'mesProduits']);

// Routes pour les commandes (authentifiées et non authentifiées)
Route::prefix('commandes')->group(function () {
    // Création de commande (accessible aux connectés et non connectés)
    Route::post('/', [CommandeController::class, 'store']);
    
    // Récupération d'une commande d'invité par numéro et email
    Route::post('/invite/recherche', [CommandeController::class, 'getCommandeInvite']);
    
    // Affichage d'une commande spécifique (accessible aux connectés et non connectés)
    Route::get('/{id}', [CommandeController::class, 'show']);
    
    // Annulation d'une commande (accessible aux connectés et non connectés)
    Route::patch('/{id}/annuler', [CommandeController::class, 'annuler']);
});
    // ... route pour permettre au vendeur de modifier le statut et Statitistique du vendeur

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/commandes/{id}/statut', [CommandeController::class, 'updateStatut']);

Route::get('/ventes-vendeur/{type}', [StatistiqueController::class, 'ventesVendeurParPeriode']);
Route::get('/meilleurs-clients-vendeur', [StatistiqueController::class, 'meilleursClientsVendeur']);
});



// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    // Liste des commandes de l'utilisateur connecté
    Route::get('/commandes', [CommandeController::class, 'index']);
    
    // Autres routes qui nécessitent une authentification...
});

/*Route::middleware('auth:sanctum')->group(function () {
    Route::post('/commandes', [CommandeController::class, 'store']);
});*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/boutique/{id}/commandes', [CommandeController::class, 'getCommandesByBoutique']);
});



