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
        Schema::create('paiments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('montantTotal');
            $table->enum('status',['en attente','reussi','echoue'])->default('en attente');
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->foreignId('type_paiement_id')->constrained('type_paiements')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiments');
    }
};
