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
        Schema::create('couples', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->foreignId('male_id')->constrained('pigeons')->cascadeOnDelete();
            $table->foreignId('femelle_id')->constrained('pigeons')->cascadeOnDelete();
            $table->foreignId('cage_id')->nullable()->constrained('cages')->nullOnDelete();
            $table->date('date_formation');
            $table->date('date_rupture')->nullable();
            $table->enum('statut', ['ACTIF', 'ROMPU'])->default('ACTIF');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index pour optimisation
            $table->index('code');
            $table->index('statut');
            $table->index(['male_id', 'femelle_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couples');
    }
};
