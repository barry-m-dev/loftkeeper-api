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
        Schema::create('sorties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pigeon_id')->constrained('pigeons')->cascadeOnDelete();
            $table->enum('type', ['VENTE', 'DECES', 'PERTE']);
            $table->date('date_sortie');
            $table->decimal('prix', 10, 2)->nullable();
            $table->string('acheteur', 150)->nullable();
            $table->string('cause', 255)->nullable();
            $table->string('circonstance', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index pour optimisation
            $table->index('pigeon_id');
            $table->index('type');
            $table->index('date_sortie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sorties');
    }
};
