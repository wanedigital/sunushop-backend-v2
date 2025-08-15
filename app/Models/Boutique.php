<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boutique extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $fillable = [
    'nom',
    'adresse',
    'numeroCommercial', // Ou le nom exact trouvé dans la base
    'status',
    'logo',
    'id_user'
];

    // app/Models/Boutique.php

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'produit_boutiques', 'id_boutique', 'id_produit');
    }

    public function user()
    {
      return $this->belongsTo(User::class, 'id_user');
    }

}
