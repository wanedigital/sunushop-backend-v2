<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boutique extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    // app/Models/Boutique.php

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'produit_boutiques', 'id_boutique', 'id_produit');
    }

}
