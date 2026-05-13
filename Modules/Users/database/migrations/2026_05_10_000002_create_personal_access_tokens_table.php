<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Exécute les migrations.
   * Crée la table personal_access_tokens pour Sanctum (STANDARD)
   */
  public function up(): void
  {
    Schema::create('personal_access_tokens', function (Blueprint $table) {
      $table->id(); // bigint auto-increment (clé primaire)
      $table->morphs('tokenable'); // tokenable_id bigint + tokenable_type
      $table->string('name');
      $table->string('token', 64)->unique();
      $table->text('abilities')->nullable();
      $table->timestamp('last_used_at')->nullable();
      $table->timestamp('expires_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Annule les migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('personal_access_tokens');
  }
};
