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

    public function getAdminSummary()
    {
        $user = Auth::user();

        // Vérification que l'utilisateur est un administrateur
        if (!$user || $user->profil->libelle !== 'Administrateur') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent accéder à ces statistiques.'
            ], 403);
        }

        // --- Statistiques sur les Commandes ---

        // Nombre total de commandes, tous statuts confondus
        $total_orders = DB::table('commandes')->count();

        // Nombre de commandes passées par des visiteurs (non-inscrits)
        $commandes_visiteurs = DB::table('commandes')->whereNull('id_user')->count();

        // Nombre de commandes en attente de traitement
        $pending_orders = DB::table('commandes')->where('etat', 'en attente')->count();

        // --- Statistiques sur les Boutiques ---

        // Nombre de boutiques actuellement ouvertes
        $active_shops = DB::table('boutiques')->where('status', 'ouvret')->count();

        // --- Statistiques sur les Utilisateurs ---

        // Nombre total d'utilisateurs avec le profil "Client"
        $total_clients_enregistres = DB::table('users')
            ->join('profils', 'users.profil_id', '=', 'profils.id')
            ->where('profils.libelle', 'Client')
            ->count();

        // Nombre total d'utilisateurs avec le profil "Vendeur"
        $total_vendors = DB::table('users')
            ->join('profils', 'users.profil_id', '=', 'profils.id')
            ->where('profils.libelle', 'Vendeur')
            ->count();

        // Assemblage des données pour la réponse JSON
        $summary = [
            'total_orders' => $total_orders,
            'pending_orders' => $pending_orders,
            'commandes_visiteurs' => $commandes_visiteurs,
            'active_shops' => $active_shops,
            'total_clients_enregistres' => $total_clients_enregistres,
            'total_vendors' => $total_vendors,
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function croissanceUtilisateurs($periode = 'mois')
    {
        $user = Auth::user();
        // Vérification que l'utilisateur est un administrateur
        if (!$user || $user->profil->libelle !== 'Administrateur') {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        // Détermination du format de date pour le groupement SQL en fonction de la période choisie
        $format = match ($periode) {
            'annee' => '%%Y',
            'semaine' => '%%Y-%%v', // Numéro de semaine ISO 8601
            'jour' => '%%Y-%%m-%%d',
            default => '%%Y-%%m', // Par défaut, par mois
        };

        // Requête pour agréger les nouvelles inscriptions
        $stats = DB::table('users')
            ->join('profils', 'users.profil_id', '=', 'profils.id')
            ->select(
                // Formate la date de création pour le groupement
                DB::raw("DATE_FORMAT(users.created_at, '$format') as date"),
                // Compte les nouveaux clients pour cette période
                DB::raw("SUM(CASE WHEN profils.libelle = 'Client' THEN 1 ELSE 0 END) as nouveaux_clients"),
                // Compte les nouveaux vendeurs pour cette période
                DB::raw("SUM(CASE WHEN profils.libelle = 'Vendeur' THEN 1 ELSE 0 END) as nouveaux_vendeurs")
            )
            ->whereIn('profils.libelle', ['Client', 'Vendeur'])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'periode' => $periode,
            'data' => $stats
        ]);
    }

    public function classementBoutiques($limit = 10)
    {
        $user = Auth::user();
        // Vérification que l'utilisateur est un administrateur
        if (!$user || $user->profil->libelle !== 'Administrateur') {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        // Requête pour classer les boutiques par nombre de commandes
        $topBoutiques = DB::table('boutiques as b')
            // Joindre les produits de la boutique
            ->join('produit_boutiques as pb', 'b.id', '=', 'pb.id_boutique')
            // Joindre les détails des commandes contenant ces produits
            ->join('detail_commandes as dc', 'pb.id_produit', '=', 'dc.produit_id')
            // Joindre les commandes pour compter
            ->join('commandes as c', 'dc.commande_id', '=', 'c.id')
            // Joindre les utilisateurs pour obtenir le nom du vendeur
            ->join('users as u', 'b.id_user', '=', 'u.id')
            // Sélectionner les informations pertinentes
            ->select(
                'b.id as boutique_id',
                'b.nom as nom_boutique',
                'b.status as statut',
                DB::raw("CONCAT(u.prenom, ' ', u.nom) as nom_vendeur"),
                // Compter les commandes distinctes pour chaque boutique
                DB::raw('COUNT(DISTINCT c.id) as nombre_commandes')
            )
            ->groupBy('b.id', 'b.nom', 'b.status', 'nom_vendeur')
            ->orderByDesc('nombre_commandes')
            ->limit((int)$limit) // Limiter le nombre de résultats
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topBoutiques
        ]);
    }

    public function classementProduits($limit = 10)
    {
        $user = Auth::user();
        // Vérification que l'utilisateur est un administrateur
        if (!$user || $user->profil->libelle !== 'Administrateur') {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        // Requête pour classer les produits par quantité vendue
        $topProduits = DB::table('produits as p')
            // Joindre les détails de commande pour obtenir les quantités
            ->join('detail_commandes as dc', 'p.id', '=', 'dc.produit_id')
            // Joindre les commandes pour filtrer par état
            ->join('commandes as c', 'dc.commande_id', '=', 'c.id')
            // Joindre les catégories pour l'information contextuelle
            ->join('categories as cat', 'p.categorie_id', '=', 'cat.id')
            // On ne compte que les produits des commandes validées, en cours ou terminées
            ->whereIn('c.etat', ['valider', 'en cours', 'terminer'])
            ->select(
                'p.id as produit_id',
                'p.libelle as nom_produit',
                'cat.libelle as categorie',
                // Sommer les quantités vendues pour chaque produit
                DB::raw('SUM(dc.quantite) as quantite_vendue')
            )
            ->groupBy('p.id', 'p.libelle', 'cat.libelle')
            ->orderByDesc('quantite_vendue')
            ->limit((int)$limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topProduits
        ]);
    }
}