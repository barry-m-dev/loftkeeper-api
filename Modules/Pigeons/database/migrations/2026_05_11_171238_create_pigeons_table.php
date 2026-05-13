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
        Schema::create('pigeons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('bague', 50)->comment('Numéro système auto-généré (P0001, immuable)');
            $table->string('bague_physique', 50)->nullable()->comment('Numéro gravé sur la vraie bague (optionnel, éditable)');
            $table->string('nom', 100)->nullable();
            $table->string('photo', 255)->nullable(); // Chemin relatif de la photo (ex: pigeons/uuid.jpg)
            $table->enum('sexe', ['MALE', 'FEMELLE']);
            $table->date('date_naissance')->nullable();
            $table->string('race', 100)->nullable();
            $table->string('couleur', 100)->nullable();
            $table->enum('statut', ['ACTIF', 'VENDU', 'MORT', 'PERDU'])->default('ACTIF');
            $table->foreignId('pere_id')->nullable()->constrained('pigeons')->nullOnDelete();
            $table->foreignId('mere_id')->nullable()->constrained('pigeons')->nullOnDelete();
            $table->foreignId('cage_id')->nullable()->constrained('cages')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index pour optimisation
            $table->index('bague');
            $table->index('statut');
            $table->index('user_id');
            $table->index(['pere_id', 'mere_id']);

            // Index unique composite pour bague et bague_physique par user
            $table->unique(['user_id', 'bague'], 'pigeons_user_bague_unique');
            $table->unique(['user_id', 'bague_physique'], 'pigeons_user_bague_physique_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pigeons');
    }
};
