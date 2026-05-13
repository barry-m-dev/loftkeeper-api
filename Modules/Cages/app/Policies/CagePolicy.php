<?php

namespace Modules\Cages\Policies;

use Modules\Cages\Models\Cage;
use Modules\Users\Models\User;

/**
 * Policy pour la gestion des autorisations sur les cages
 * 
 * @package Modules\Cages\Policies
 */
class CagePolicy
{
  /**
   * Liste globale : toujours autorisé (Global Scope filtre automatiquement)
   * 
   * @param User $user
   * @return bool
   */
  public function viewAny(User $user): bool
  {
    return true;
  }

  /**
   * Voir le détail d'une cage
   * 
   * @param User $user
   * @param Cage $cage
   * @return bool
   */
  public function view(User $user, Cage $cage): bool
  {
    return $cage->isOwnedBy($user->id);
  }

  /**
   * Créer une cage
   * 
   * @param User $user
   * @return bool
   */
  public function create(User $user): bool
  {
    return true;
  }

  /**
   * Modifier une cage
   * 
   * @param User $user
   * @param Cage $cage
   * @return bool
   */
  public function update(User $user, Cage $cage): bool
  {
    return $cage->isOwnedBy($user->id);
  }

  /**
   * Supprimer une cage (seulement si LIBRE)
   * 
   * @param User $user
   * @param Cage $cage
   * @return bool
   */
  public function delete(User $user, Cage $cage): bool
  {
    return $cage->isOwnedBy($user->id) && $cage->isLibre();
  }

  /**
   * Affecter un pigeon ou couple à une cage
   * 
   * @param User $user
   * @param Cage $cage
   * @return bool
   */
  public function affecter(User $user, Cage $cage): bool
  {
    return $cage->isOwnedBy($user->id) && $cage->isLibre();
  }

  /**
   * Libérer une cage
   * 
   * @param User $user
   * @param Cage $cage
   * @return bool
   */
  public function liberer(User $user, Cage $cage): bool
  {
    return $cage->isOwnedBy($user->id) && $cage->isOccupee();
  }
}
