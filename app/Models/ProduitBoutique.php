<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduitBoutique extends Model
{
    use HasFactory;
      protected $fillable = ['id_produit', 'id_boutique'];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'id_produit');
    }

    public function boutique()
    {
        //return $this->belongsTo(Boutique::class, 'id_boutique');
    }
}
