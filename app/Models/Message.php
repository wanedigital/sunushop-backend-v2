<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'expediteur_id',
        'destinataire_id',
        'sujet',
        'contenu',
        'lu_at',
        'commande_id',
        'produit_id',
    ];

    public function expediteur()
    {
        return $this->belongsTo(User::class, 'expediteur_id');
    }

    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    // Scopes utiles
    public function scopeNonLus($query)
    {
        return $query->whereNull('lu_at');
    }

    public function scopeRecusPar($query, $userId)
    {
        return $query->where('destinataire_id', $userId);
    }

    public function scopeEnvoyesPar($query, $userId)
    {
        return $query->where('expediteur_id', $userId);
    }
}
