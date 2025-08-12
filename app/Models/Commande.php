<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'numeroCommande',
        'date',
        'etat',
        'total',
        'id_user',
        'nom_client',
        'prenom_client',
        'telephone_client',
        'adresse_client',
        'email_client',
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'total' => 'integer'
    ];

    protected $attributes = [
        'etat' => 'en attente'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function detailCommandes()
    {
        return $this->hasMany(DetailCommande::class);
    }

    // Accesseurs
    public function getNomCompletClientAttribute()
    {
        if ($this->user) {
            return $this->user->nom . ' ' . $this->user->prenom;
        }
        return $this->nom_client . ' ' . $this->prenom_client;
    }

    public function getTelephoneClientAttribute($value)
    {
        if ($this->user && $this->user->telephone) {
            return $this->user->telephone;
        }
        return $value;
    }

    public function getAdresseClientAttribute($value)
    {
        if ($this->user && $this->user->adresse) {
            return $this->user->adresse;
        }
        return $value;
    }

    public function getEmailClientAttribute($value)
    {
        if ($this->user && $this->user->email) {
            return $this->user->email;
        }
        return $value;
    }

    // Méthodes utilitaires
    public static function genererNumeroCommande()
    {
        $date = now()->format('Ymd');
        $derniereCommande = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $numero = $derniereCommande ? (int)substr($derniereCommande->numeroCommande, -4) + 1 : 1;
        
        return 'CMD' . $date . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public function peutEtreAnnulee()
    {
        return in_array($this->etat, ['en attente', 'valider']);
    }

    // Scopes
    public function scopeParEtat($query, $etat)
    {
        return $query->where('etat', $etat);
    }

    public function scopeParUtilisateur($query, $userId)
    {
        return $query->where('id_user', $userId);
    }

    public function scopeAujourdhui($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Boot method pour générer automatiquement le numéro de commande
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($commande) {
            $commande->numeroCommande = self::genererNumeroCommande();
            $commande->date = now()->toDateString();
        });
    }

    public function meilleursClients()
{
    $user = Auth::user();

    if (!$user || $user->profil->libelle !== 'Vendeur') {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé'
        ], 403);
    }

    $clients = Commande::select(
            DB::raw('COALESCE(nom_client, users.name) as nom'),
            DB::raw('COALESCE(prenom_client, "") as prenom'),
            DB::raw('COALESCE(email_client, users.email) as email'),
            DB::raw('COUNT(commandes.id) as nombre_commandes'),
            DB::raw('SUM(total) as total_depense')
        )
        ->leftJoin('users', 'commandes.id_user', '=', 'users.id')
        ->where('commandes.id_user', $user->id)
        ->groupBy('nom', 'prenom', 'email')
        ->orderByDesc('total_depense')
        ->limit(10)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $clients
    ]);
}
public function ventesAnnuelles()
{
    $user = Auth::user();

    if (!$user || $user->profil->libelle !== 'Vendeur') {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé'
        ], 403);
    }

    $stats = Commande::select(
            DB::raw('YEAR(date) as annee'),
            DB::raw('SUM(total) as total_ventes'),
            DB::raw('COUNT(*) as nombre_commandes')
        )
        ->where('id_user', $user->id)
        ->groupBy(DB::raw('YEAR(date)'))
        ->orderBy(DB::raw('YEAR(date)'))
        ->get();

    return response()->json([
        'success' => true,
        'data' => $stats
    ]);
}
public function ventesMensuelles()
{
    $user = Auth::user();

    if (!$user || $user->profil->libelle !== 'Vendeur') {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé'
        ], 403);
    }

    // Statistiques par mois pour l'année en cours
    $stats = Commande::select(
            DB::raw('MONTH(date) as mois'),
            DB::raw('SUM(total) as total_ventes'),
            DB::raw('COUNT(*) as nombre_commandes')
        )
        ->where('id_user', $user->id)
        ->whereYear('date', now()->year)
        ->groupBy(DB::raw('MONTH(date)'))
        ->orderBy(DB::raw('MONTH(date)'))
        ->get();

    return response()->json([
        'success' => true,
        'data' => $stats
    ]);
}

}