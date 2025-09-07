<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'produit_id',
        'user_id',
        'note',
        'commentaire',
        'statut',
    ];

    protected $casts = [
        'note' => 'integer',
    ];

    public static function rules()
    {
        return [
            'note' => 'required|integer|between:1,5',
            'commentaire' => 'nullable|string|max:1000',
            'produit_id' => 'required|exists:produits,id',
        ];
    }

    public function scopeApprouve($query)
    {
        return $query->where('statut', 'approuve');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopePourProduit($query, $produitId)
    {
        return $query->where('produit_id', $produitId);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
