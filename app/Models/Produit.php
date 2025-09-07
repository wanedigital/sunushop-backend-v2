<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function boutiques()
    {
        return $this->belongsToMany(Boutique::class, 'produit_boutiques', 'id_produit', 'id_boutique');
    }

    // Accesseur pour l'URL complète de l'image
    public function getImageAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return null;
    }

    public function commandes()
    {
        return $this->hasManyThrough(Commande::class, DetailCommande::class, 'produit_id', 'id', 'id', 'commande_id');
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function noteMoyenne()
    {
        return $this->evaluations()->approuve()->avg('note') ?? 0;
    }

    public function nombreEvaluations()
    {
        return $this->evaluations()->approuve()->count();
    }

    protected $fillable = [
        'libelle',
        'description',
        'prix',
        'quantite',
        'image',
        'disponible',
        'categorie_id',
        'note_moyenne',
        'nombre_evaluations',
    ];
}