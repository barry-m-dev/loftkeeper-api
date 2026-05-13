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
        Schema::create('cages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('numero', 20)->unique();
            $table->string('nom', 100)->nullable();
            $table->decimal('superficie', 5, 2)->nullable();
            $table->enum('statut', ['LIBRE', 'OCCUPE_PIGEON', 'OCCUPE_COUPLE'])->default('LIBRE');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index pour optimisation
            $table->index('numero');
            $table->index('statut');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cages');
    }
};
