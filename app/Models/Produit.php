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

    protected $fillable =
     [
        'libelle', 
        'description',
        'prix',
        'quantite',
        'image',
        'disponible',
        'categorie_id'
   ]; 

}