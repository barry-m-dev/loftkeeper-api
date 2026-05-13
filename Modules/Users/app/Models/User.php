<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Model User - Utilisateur de l'application
 * 
 * Utilise UUID comme clé primaire pour la sécurité
 * Gère l'authentification avec Sanctum et les permissions avec Spatie
 */
class User extends Authenticatable
{
  use HasFactory, Notifiable, HasRoles, SoftDeletes, HasApiTokens;

  /**
   * Guard par défaut pour Spatie Permission
   */
  protected string $guard_name = 'web';

  /**
   * Attributs assignables en masse
   */
  protected $fillable = [
    'uuid',
    'first_name',
    'last_name',
    'email',
    'phone',
    'password',
    'avatar',
    'role',
    'status',
    'two_factor_code',
    'two_factor_expires_at',
    'two_factor_enabled',
    'otp_required',
  ];

  /**
   * Attributs cachés dans les réponses JSON
   */
  protected $hidden = [
    'password',
    'remember_token',
    'two_factor_code',
  ];

  /**
   * Casts des attributs
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'two_factor_expires_at' => 'datetime',
      'two_factor_enabled' => 'boolean',
      'otp_required' => 'boolean',
      'password' => 'hashed',
    ];
  }

  /**
   * Boot du modèle - génère UUID automatiquement
   */
  protected static function boot(): void
  {
    parent::boot();

    static::creating(function ($model) {
      if (empty($model->uuid)) {
        $model->uuid = (string) Str::uuid();
      }
    });
  }

  /**
   * Utilise UUID comme clé de route
   */
  public function getRouteKeyName(): string
  {
    return 'uuid';
  }

  /**
   * Récupère le nom complet de l'utilisateur
   */
  public function getFullNameAttribute(): string
  {
    return "{$this->first_name} {$this->last_name}";
  }

  /**
   * Scope pour les utilisateurs actifs
   */
  public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
  {
    return $query->where('status', 'active');
  }

  /**
   * Scope pour les utilisateurs inactifs
   */
  public function scopeInactive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
  {
    return $query->where('status', 'inactive');
  }

  /**
   * Scope pour les utilisateurs suspendus
   */
  public function scopeSuspended(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
  {
    return $query->where('status', 'suspended');
  }
}
