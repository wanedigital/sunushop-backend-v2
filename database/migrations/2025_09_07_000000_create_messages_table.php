<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expediteur_id');
            $table->unsignedBigInteger('destinataire_id');
            $table->string('sujet')->nullable();
            $table->text('contenu');
            $table->timestamp('lu_at')->nullable();
            $table->timestamps();

            $table->foreign('expediteur_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('destinataire_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
