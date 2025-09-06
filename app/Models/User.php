<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Profil;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
       protected $fillable = [
        'nom',
        'prenom',
        'adresse',
        'telephone',
        'email',
        'password',
        'photo', // Ajout du champ photo
        'status',
        'profil_id',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'string',
    ];


    // Relation : un utilisateur appartient à un profil
    public function profil()
    {
        return $this->belongsTo(Profil::class, 'profil_id');
    }

    // Vérifier si l'utilisateur a un profil spécifique
   /* public function hasProfil($libelle)
    {
        return $this->profil && $this->profil->libelle === $libelle;
    }*/

    
    /**
     * Vérifie si l'utilisateur a un rôle spécifique.
     *
     * @param  string  $role  Le rôle à vérifier (ex: 'Administrateur', 'Client', 'Vendeur')
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->profil && $this->profil->libelle === $role;
    }
        public function boutique()
        {
            return $this->hasOne(Boutique::class, 'id_user');
        }

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class, 'user_id');
    }


}
