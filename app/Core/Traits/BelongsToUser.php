<?php

namespace App\Core\Traits;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait BelongsToUser
 * 
 * Trait réutilisable pour tous les modèles qui appartiennent à un utilisateur.
 * Applique automatiquement le Global Scope pour filtrer par user_id.
 * 
 * @package App\Core\Traits
 */
trait BelongsToUser
{
  /**
   * Boot du trait : applique le Global Scope et injecte user_id à la création
   */
  protected static function bootBelongsToUser(): void
  {
    // Appliquer le Global Scope pour filtrer automatiquement par user_id
    static::addGlobalScope(new UserScope());

    // Injecter automatiquement le user_id lors de la création
    static::creating(function ($model) {
      if (auth()->check() && !$model->user_id) {
        $model->user_id = auth()->id();
      }
    });
  }

  /**
   * Vérifie si le modèle appartient à l'utilisateur donné
   * 
   * @param int $userId ID de l'utilisateur
   * @return bool
   */
  public function isOwnedBy(int $userId): bool
  {
    return (int) $this->user_id === $userId;
  }

  /**
   * Scope pour filtrer manuellement par user_id
   * 
   * @param Builder $query
   * @param int $userId
   * @return Builder
   */
  public function scopeForUser(Builder $query, int $userId): Builder
  {
    return $query->where($this->getTable() . '.user_id', $userId);
  }
}
