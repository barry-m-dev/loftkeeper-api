<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Exécute les migrations.
   * Crée la table users avec UUID et toutes les colonnes nécessaires
   */
  public function up(): void
  {
    Schema::create('users', function (Blueprint $table) {
      $table->id(); // Clé primaire auto-increment
      $table->uuid('uuid')->unique(); // UUID pour identification externe
      $table->string('first_name');
      $table->string('last_name');
      $table->string('email')->unique();
      $table->timestamp('email_verified_at')->nullable();
      $table->string('password');
      $table->string('phone', 20)->unique()->nullable(); // Téléphone unique pour connexion
      $table->string('avatar')->nullable();
      $table->enum('role', ['CLIENT', 'ADMIN'])->default('CLIENT'); // Rôle utilisateur
      $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
      $table->boolean('otp_required')->default(true); // Indique si l'OTP est requis (true par défaut, false après 1ère connexion)
      $table->rememberToken();
      $table->timestamps();
      $table->softDeletes();
    });

    Schema::create('password_reset_tokens', function (Blueprint $table) {
      $table->string('email')->primary();
      $table->string('token');
      $table->timestamp('created_at')->nullable();
    });

    Schema::create('sessions', function (Blueprint $table) {
      $table->string('id')->primary();
      $table->foreignId('user_id')->nullable()->index();
      $table->string('ip_address', 45)->nullable();
      $table->text('user_agent')->nullable();
      $table->longText('payload');
      $table->integer('last_activity')->index();
    });
  }

  /**
   * Annule les migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('sessions');
    Schema::dropIfExists('password_reset_tokens');
    Schema::dropIfExists('users');
  }
};
