<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\API\ProfilValidationController;
use App\Http\Controllers\Api\CommandeController;


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

    // Client : soumettre une évaluation
    Route::post('/evaluations', [App\Http\Controllers\EvaluationController::class, 'store']);

    // Messagerie : client envoie un message à un vendeur
    Route::post('/messages/envoyer', [App\Http\Controllers\MessagerieController::class, 'envoyer']);
    // Messagerie : messages reçus (pagination)
    Route::get('/messages/recus', [App\Http\Controllers\MessagerieController::class, 'recus']);
    // Messagerie : messages envoyés (pagination)
    Route::get('/messages/envoyes', [App\Http\Controllers\MessagerieController::class, 'envoyes']);
    // Messagerie : conversations
    Route::get('/messages/conversations', [App\Http\Controllers\MessagerieController::class, 'conversations']);
    // Messagerie : marquer un message comme lu
    Route::post('/messages/{id}/marquer-lu', [App\Http\Controllers\MessagerieController::class, 'marquerCommeLu']);
    // Messagerie : répondre à un message
    Route::post('/messages/{id}/repondre', [App\Http\Controllers\MessagerieController::class, 'repondre']);
    // Messagerie : marquer toute une conversation comme lue
    Route::post('/messages/conversation/{userId}/marquer-lus', [App\Http\Controllers\MessagerieController::class, 'marquerConversationCommeLue']);
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


Route::apiResource('categories', \App\Http\Controllers\CategorieController::class);
Route::apiResource('produits', \App\Http\Controllers\ProduitController::class);

// Public/Client : voir évaluations d'un produit
Route::get('/produits/{produit}/evaluations', [App\Http\Controllers\EvaluationController::class, 'index']);

// Routes améliorées pour évaluations
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/evaluations', [App\Http\Controllers\EvaluationController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:Administrateur'])->group(function () {
    Route::put('/evaluations/{id}/moderate', [App\Http\Controllers\EvaluationController::class, 'moderate']);
    Route::get('/evaluations/en-attente', [App\Http\Controllers\EvaluationController::class, 'evaluationsEnAttente']);
});

Route::get('/boutiques/{id}/produits', [BoutiqueController::class, 'produits']);

// route qui permet de modifier le produit du client en vendeur lorsqu'il cree une boutique
Route::get('/valider-vendeur', [ProfilValidationController::class, 'valider'])
    ->name('api.boutique.approuver-vendeur');



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

// Admin : modération des évaluations
Route::middleware(['auth:sanctum', 'role:Administrateur'])->patch('/admin/evaluations/{id}/moderate', [App\Http\Controllers\EvaluationController::class, 'moderate']);

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



