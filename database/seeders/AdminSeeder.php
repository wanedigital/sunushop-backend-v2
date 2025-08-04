<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifie si déjà existant
        $existing = User::where('email', 'brice@gmail.com')->first();
        if (!$existing) {
            $user = User::create([
                'nom' => 'Mendy',
                'prenom' => 'Brice',
                'adresse' => 'Dakar',
                'telephone' => '776789043',
                'email' => 'brice@gmail.com',
                'password' => Hash::make('vrai2025#'), // bcrypt
                'status' => 'actif',
                'profil_id' => 1,
            ]);

            $user->save();

            echo "Administrateur créé: brice@gmail.com / password\n";
        } else {
            echo "Administrateur déjà existant\n";
        }
    }
}
