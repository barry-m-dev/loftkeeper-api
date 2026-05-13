<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Exécute les migrations.
   * Ajoute les colonnes pour l'authentification à deux facteurs (OTP)
   */
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('two_factor_code')->nullable()->after('password');
      $table->dateTime('two_factor_expires_at')->nullable()->after('two_factor_code');
      $table->boolean('two_factor_enabled')->default(false)->after('two_factor_expires_at');
    });
  }

  /**
   * Annule les migrations.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['two_factor_code', 'two_factor_expires_at', 'two_factor_enabled']);
    });
  }
};
