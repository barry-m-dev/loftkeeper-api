<?php

namespace Modules\Cages\Services;

use Modules\Cages\Models\Cage;
use Modules\Pigeons\Models\Pigeon;
use Modules\Couples\Models\Couple;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Service pour la gestion de la logique métier des cages
 */
class CageService
{
  /**
   * Récupérer toutes les cages avec filtres
   *
   * @param array $filters
   * @return Collection
   */
  public function getAllCages(array $filters = []): Collection
  {
    $query = Cage::query();

    // Filtre par statut
    if (isset($filters['statut'])) {
      $query->where('statut', $filters['statut']);
    }

    // Recherche
    if (isset($filters['search'])) {
      $search = $filters['search'];
      $query->where(function ($q) use ($search) {
        $q->where('numero', 'like', "%{$search}%")
          ->orWhere('nom', 'like', "%{$search}%");
      });
    }

    return $query->with(['pigeon', 'couple.male', 'couple.femelle'])
      ->orderBy('numero')
      ->get();
  }

  /**
   * Obtenir les statistiques des cages
   *
   * @param Collection|null $cages
   * @return array
   */
  public function getStats(?Collection $cages = null): array
  {
    if ($cages === null) {
      $cages = Cage::all();
    }

    $lastCage = Cage::orderBy('numero', 'desc')->first();
    $nextNumero = $this->getNextNumero();

    return [
      'total' => $cages->count(),
      'libres' => $cages->where('statut', 'LIBRE')->count(),
      'occupees_pigeon' => $cages->where('statut', 'OCCUPE_PIGEON')->count(),
      'occupees_couple' => $cages->where('statut', 'OCCUPE_COUPLE')->count(),
      'last_numero' => $lastCage ? $lastCage->numero : null,
      'next_numero' => $nextNumero,
    ];
  }

  /**
   * Générer le prochain numéro de cage
   * Utilise withTrashed() pour continuer la numérotation même après soft delete
   *
   * @return string
   */
  public function getNextNumero(): string
  {
    $lastCage = Cage::withTrashed()->orderBy('numero', 'desc')->first();

    if (!$lastCage) {
      return 'C001';
    }

    // Extraire le nombre du numéro (C001 -> 1)
    $lastNumber = (int) substr($lastCage->numero, 1);
    $nextNumber = $lastNumber + 1;

    // Formater avec padding (1 -> C001)
    return 'C' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
  }

  /**
   * Créer une nouvelle cage
   *
   * @param array $data
   * @param string $userId
   * @return Cage
   */
  public function createCage(array $data, string $userId): Cage
  {
    $data['numero'] = $this->getNextNumero();
    $data['user_id'] = $userId;
    $data['statut'] = 'LIBRE';
    $data['uuid'] = Str::uuid()->toString();

    return Cage::create($data);
  }

  /**
   * Mettre à jour une cage
   *
   * @param Cage $cage
   * @param array $data
   * @return Cage
   */
  public function updateCage(Cage $cage, array $data): Cage
  {
    // Sécurité : ne jamais permettre la modification du numéro
    unset($data['numero']);
    unset($data['user_id']);
    unset($data['statut']);

    $cage->update($data);
    return $cage->fresh();
  }

  /**
   * Supprimer une cage (soft delete - seulement si libre)
   *
   * @param Cage $cage
   * @return bool
   * @throws \Exception
   */
  public function deleteCage(Cage $cage): bool
  {
    if (!$cage->isLibre()) {
      throw new \Exception('Impossible de supprimer une cage occupée');
    }

    return $cage->delete(); // Soft delete automatique grâce au trait SoftDeletes
  }

  /**
   * Affecter un pigeon à une cage
   *
   * @param Cage $cage
   * @param Pigeon $pigeon
   * @return Cage
   * @throws \Exception
   */
  public function affecterPigeon(Cage $cage, Pigeon $pigeon): Cage
  {
    // Vérifications
    if (!$cage->isLibre()) {
      throw new \Exception('Cette cage est déjà occupée');
    }

    if ($pigeon->statut !== 'ACTIF') {
      throw new \Exception('Ce pigeon n\'est pas actif');
    }

    if ($pigeon->cage_id) {
      throw new \Exception('Ce pigeon est déjà dans une cage');
    }

    // Affectation
    $cage->update(['statut' => 'OCCUPE_PIGEON']);
    $pigeon->update(['cage_id' => $cage->id]);

    return $cage->load(['pigeon']);
  }

  /**
   * Affecter un couple à une cage
   *
   * @param Cage $cage
   * @param Couple $couple
   * @return Cage
   * @throws \Exception
   */
  public function affecterCouple(Cage $cage, Couple $couple): Cage
  {
    // Vérifications
    if (!$cage->isLibre()) {
      throw new \Exception('Cette cage est déjà occupée');
    }

    if ($couple->statut !== 'ACTIF') {
      throw new \Exception('Ce couple n\'est pas actif');
    }

    if ($couple->cage_id) {
      throw new \Exception('Ce couple est déjà dans une cage');
    }

    // Affectation
    $cage->update(['statut' => 'OCCUPE_COUPLE']);
    $couple->update(['cage_id' => $cage->id]);

    return $cage->load(['couple.male', 'couple.femelle']);
  }

  /**
   * Libérer une cage
   *
   * @param Cage $cage
   * @return Cage
   * @throws \Exception
   */
  public function liberer(Cage $cage): Cage
  {
    if ($cage->isLibre()) {
      throw new \Exception('Cette cage est déjà libre');
    }

    // Libération selon le type d'occupant
    if ($cage->statut === 'OCCUPE_PIGEON') {
      Pigeon::where('cage_id', $cage->id)->update(['cage_id' => null]);
    }

    if ($cage->statut === 'OCCUPE_COUPLE') {
      Couple::where('cage_id', $cage->id)->update(['cage_id' => null]);
    }

    $cage->update(['statut' => 'LIBRE']);

    return $cage->fresh();
  }
}
