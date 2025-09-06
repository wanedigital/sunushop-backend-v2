<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->enum('statut', ['actif', 'expire', 'annule', 'en_grace'])->default('actif');
            $table->enum('statut_paiement', ['paye', 'en_attente', 'echoue', 'essai'])->default('en_attente');
            $table->timestamp('date_dernier_rappel_proactif')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};