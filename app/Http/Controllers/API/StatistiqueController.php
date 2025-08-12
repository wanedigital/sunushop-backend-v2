<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class StatistiqueController extends Controller
{

   public function ventesVendeurParPeriode($type)
{
    $user = Auth::user();

    if (!$user || $user->profil->libelle !== 'Vendeur') {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé'
        ], 403);
    }

    $query = DB::table('commandes')
        ->join('detail_commandes', 'commandes.id', '=', 'detail_commandes.commande_id')
        ->join('produits', 'detail_commandes.produit_id', '=', 'produits.id')
        ->join('produit_boutiques', 'produits.id', '=', 'produit_boutiques.id_produit')
        ->join('boutiques', 'produit_boutiques.id_boutique', '=', 'boutiques.id')
        ->where('boutiques.id_user', $user->id);

    if ($type === 'mois') {
        $query->whereRaw('EXTRACT(YEAR FROM commandes.date) = ?', [now()->year])
            ->selectRaw('
                EXTRACT(MONTH FROM commandes.date) as periode,
                SUM(detail_commandes.quantite * detail_commandes.prixunitaire) as total_ventes,
                COUNT(DISTINCT commandes.id) as nombre_commandes
            ')
            ->groupBy(DB::raw('EXTRACT(MONTH FROM commandes.date)'))
            ->orderBy('periode');
    } elseif ($type === 'annee') {
        $query->selectRaw('
                EXTRACT(YEAR FROM commandes.date) as periode,
                SUM(detail_commandes.quantite * detail_commandes.prixunitaire) as total_ventes,
                COUNT(DISTINCT commandes.id) as nombre_commandes
            ')
            ->groupBy(DB::raw('EXTRACT(YEAR FROM commandes.date)'))
            ->orderBy('periode');
    } elseif ($type === 'semaine') {
        $query->whereRaw('EXTRACT(YEAR FROM commandes.date) = ?', [now()->year])
            ->selectRaw('
                EXTRACT(WEEK FROM commandes.date) as periode,
                SUM(detail_commandes.quantite * detail_commandes.prixunitaire) as total_ventes,
                COUNT(DISTINCT commandes.id) as nombre_commandes
            ')
            ->groupBy(DB::raw('EXTRACT(WEEK FROM commandes.date)'))
            ->orderBy('periode');
    } elseif ($type === 'semestriel') {
    $query->whereRaw('EXTRACT(YEAR FROM commandes.date) = ?', [now()->year])
        ->selectRaw('
            CASE 
                WHEN EXTRACT(MONTH FROM commandes.date) BETWEEN 1 AND 6 THEN 1
                ELSE 2
            END as periode,
            SUM(detail_commandes.quantite * detail_commandes.prixunitaire) as total_ventes,
            COUNT(DISTINCT commandes.id) as nombre_commandes
        ')
        ->groupBy(DB::raw('CASE WHEN EXTRACT(MONTH FROM commandes.date) BETWEEN 1 AND 6 THEN 1 ELSE 2 END'))
        ->orderBy('periode');
    }else {
        return response()->json([
            'success' => false,
            'message' => 'Type de période invalide. Utilisez "mois", "annee" ou "semaine".'
        ], 400);
    }

    $stats = $query->get();

    return response()->json([
        'success' => true,
        'data' => $stats
    ]);
}

  public function meilleursClientsVendeur($type = null)
{
    $user = Auth::user();

    if (!$user || $user->profil->libelle !== 'Vendeur') {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé'
        ], 403);
    }

    $query = DB::table('commandes')
        ->join('detail_commandes', 'commandes.id', '=', 'detail_commandes.commande_id')
        ->join('produits', 'detail_commandes.produit_id', '=', 'produits.id')
        ->join('produit_boutiques', 'produits.id', '=', 'produit_boutiques.id_produit')
        ->join('boutiques', 'produit_boutiques.id_boutique', '=', 'boutiques.id')
        ->leftJoin('users', 'commandes.id_user', '=', 'users.id')
        ->where('boutiques.id_user', $user->id);

    // 🔍 Filtrage par période
    if ($type === 'mois') {
        $query->whereRaw('EXTRACT(YEAR FROM commandes.date) = ?', [now()->year])
              ->whereRaw('EXTRACT(MONTH FROM commandes.date) = ?', [now()->month]);
    } elseif ($type === 'annee') {
        $query->whereRaw('EXTRACT(YEAR FROM commandes.date) = ?', [now()->year]);
    } elseif ($type === 'semaine') {
        $query->whereRaw('EXTRACT(YEAR FROM commandes.date) = ?', [now()->year])
              ->whereRaw('EXTRACT(WEEK FROM commandes.date) = ?', [now()->weekOfYear]);
    } elseif ($type === 'semestriel') {
        $mois = now()->month;
        $semestre = $mois <= 6 ? [1, 2, 3, 4, 5, 6] : [7, 8, 9, 10, 11, 12];
        $query->whereRaw('EXTRACT(YEAR FROM commandes.date) = ?', [now()->year])
              ->whereRaw('EXTRACT(MONTH FROM commandes.date) IN (' . implode(',', $semestre) . ')');
    }

    // 📊 Sélection des meilleurs clients
    $clients = $query->selectRaw("
            COALESCE(commandes.nom_client, users.nom) as nom,
            COALESCE(commandes.prenom_client, '') as prenom,
            COALESCE(commandes.email_client, users.email) as email,
            COUNT(DISTINCT commandes.id) as nombre_commandes,
            SUM(detail_commandes.quantite * detail_commandes.prixunitaire) as total_depense,
            AVG(detail_commandes.quantite * detail_commandes.prixunitaire) as moyenne_depense,
            MAX(commandes.date) as derniere_commande
        ")
        ->groupBy(
            DB::raw('COALESCE(commandes.nom_client, users.nom)'),
            DB::raw('COALESCE(commandes.prenom_client, \'\')'),
            DB::raw('COALESCE(commandes.email_client, users.email)')
        )
        ->orderByDesc('total_depense')
        ->limit(10)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $clients
    ]);
}
}