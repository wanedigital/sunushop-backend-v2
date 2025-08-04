<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('numeroCommande')->unique()->nullable()->change();
            $table->foreignId('id_user')->nullable()->change();

            // Champs pour client invité
            $table->string('nom_client')->nullable()->after('id_user');
            $table->string('prenom_client')->nullable()->after('nom_client');
            $table->string('email_client')->nullable()->after('prenom_client');
            $table->string('telephone_client')->nullable()->after('email_client');
            $table->text('adresse_client')->nullable()->after('telephone_client');
            $table->text('notes')->nullable()->after('adresse_client');

            // Index
            $table->index('numeroCommande');
            $table->index('email_client');
            $table->index('etat');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            // Suppression des nouveaux champs
            $table->dropColumn([
                'nom_client',
                'prenom_client',
                'email_client',
                'telephone_client',
                'adresse_client',
                'notes'
            ]);

            // Suppression des index ajoutés
            $table->dropIndex(['numeroCommande']);
            $table->dropIndex(['email_client']);
            $table->dropIndex(['etat']);
            $table->dropIndex(['date']);

            // Remettre les colonnes comme avant si besoin (optionnel)
            // $table->string('numeroCommande')->nullable()->change(); 
            // $table->foreignId('id_user')->nullable(false)->change();
        });
    }
};
