<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Exécute les migrations.
   * Crée les tables pour le cache en database (pas de Redis)
   */
  public function up(): void
  {
    Schema::create('cache', function (Blueprint $table) {
      $table->string('key')->primary();
      $table->mediumText('value');
      $table->integer('expiration');
    });

    Schema::create('cache_locks', function (Blueprint $table) {
      $table->string('key')->primary();
      $table->string('owner');
      $table->integer('expiration');
    });
  }

  /**
   * Annule les migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('cache_locks');
    Schema::dropIfExists('cache');
  }
};
