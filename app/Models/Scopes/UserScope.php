<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope UserScope
 * 
 * Filtre automatiquement tous les modèles par user_id de l'utilisateur connecté.
 * Vérifie que l'utilisateur est connecté avant d'appliquer le filtre.
 * 
 * @package App\Models\Scopes
 */
class UserScope implements Scope
{
  /**
   * Applique le scope à la requête
   * 
   * @param Builder $builder
   * @param Model $model
   * @return void
   */
  public function apply(Builder $builder, Model $model): void
  {
    // Appliquer le filtre seulement si un utilisateur est connecté
    if (auth()->check()) {
      $builder->where($model->getTable() . '.user_id', auth()->id());
    }
  }
}
