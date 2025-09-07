<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('commande_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('produit_id')->nullable()->constrained()->onDelete('cascade');
            $table->index(['expediteur_id', 'created_at']);
            $table->index(['destinataire_id', 'created_at']);
            $table->index(['destinataire_id', 'lu_at']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['commande_id']);
            $table->dropForeign(['produit_id']);
            $table->dropColumn(['commande_id', 'produit_id']);
            $table->dropIndex(['expediteur_id', 'created_at']);
            $table->dropIndex(['destinataire_id', 'created_at']);
            $table->dropIndex(['destinataire_id', 'lu_at']);
        });
    }
};
