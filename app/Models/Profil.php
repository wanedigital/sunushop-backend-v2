<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
    protected $fillable = [
        'libelle',
    ];

    protected $casts = [
        'libelle' => 'string',
    ];

    // Relation : un profil peut être associé à plusieurs utilisateurs
    public function users()
    {
        return $this->hasMany(User::class, 'profil_id');
    }
}