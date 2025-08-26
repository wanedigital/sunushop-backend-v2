<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasFactory;

    protected $table = 'paiments';

    protected $fillable = [
        'commande_id',
        'type_paiement_id',
        'montantTotal',
        'status',
        'date',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function typePaiement()
    {
        return $this->belongsTo(TypePaiement::class);
    }
}
