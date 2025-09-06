<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prix',
        'devise',
        'duree_en_jours',
        'fonctionnalites',
    ];

    protected $casts = [
        'fonctionnalites' => 'array',
    ];

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class);
    }
}