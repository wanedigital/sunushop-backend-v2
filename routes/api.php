<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CategorieController;

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

Route::apiResource('boutiques', \App\Http\Controllers\BoutiqueController::class);



Route::apiResource('categories', \App\Http\Controllers\CategorieController::class);
Route::apiResource('produits', \App\Http\Controllers\ProduitController::class);

Route::get('/boutiques/{id}/produits', [BoutiqueController::class, 'produits']);



