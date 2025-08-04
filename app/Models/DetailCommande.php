<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailCommande extends Model
{
    use HasFactory;

    protected $fillable = [
        'prixunitaire',
        'quantite',
        'commande_id',
        'produit_id'
    ];

    protected $casts = [
        'prixunitaire' => 'integer',
        'quantite' => 'integer'
    ];

    // Relations
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    // Accesseurs
    public function getSousTotal()
    {
        return $this->prixunitaire * $this->quantite;
    }

    // Scopes
    public function scopeParCommande($query, $commandeId)
    {
        return $query->where('commande_id', $commandeId);
    }

    public function scopeParProduit($query, $produitId)
    {
        return $query->where('produit_id', $produitId);
    }
}