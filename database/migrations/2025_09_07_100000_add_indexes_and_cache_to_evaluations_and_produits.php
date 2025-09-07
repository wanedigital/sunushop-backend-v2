<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajout des index sur evaluations
        Schema::table('evaluations', function (Blueprint $table) {
            $table->index(['produit_id', 'statut']);
            $table->index(['user_id', 'produit_id']);
        });

        // Ajout des colonnes cache sur produits
        Schema::table('produits', function (Blueprint $table) {
            $table->decimal('note_moyenne', 3, 2)->default(0)->after('prix');
            $table->integer('nombre_evaluations')->default(0)->after('note_moyenne');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropIndex(['produit_id', 'statut']);
            $table->dropIndex(['user_id', 'produit_id']);
        });
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn(['note_moyenne', 'nombre_evaluations']);
        });
    }
};
