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
        Schema::create('reproductions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('couple_id')->constrained('couples')->cascadeOnDelete();
            $table->date('date_ponte');
            $table->tinyInteger('nb_oeufs');
            $table->date('date_eclosion')->nullable();
            $table->tinyInteger('nb_pigeonneaux')->nullable();
            $table->date('date_sevrage')->nullable();
            $table->enum('statut', ['PONTE', 'ECLOSION', 'SEVRAGE', 'ENREGISTRE', 'ECHEC'])->default('PONTE');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index pour optimisation
            $table->index('couple_id');
            $table->index('statut');
            $table->index('date_ponte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reproductions');
    }
};
