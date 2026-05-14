<?php

namespace Modules\Pigeons\Services;

use Modules\Pigeons\Models\Pigeon;
use Modules\Cages\Models\Cage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Services\NumberingService;

/**
 * Service pour la gestion de la logique métier des pigeons
 * 
 * @package Modules\Pigeons\Services
 */
class PigeonService
{
  protected NumberingService $numberingService;

  public function __construct(NumberingService $numberingService)
  {
    $this->numberingService = $numberingService;
  }

  /**
   * Récupérer tous les pigeons avec filtres
   *
   * @param array $filters
   * @return Collection
   */
  public function getAllPigeons(array $filters = []): Collection
  {
    $query = Pigeon::query();

    // Filtre par statut (état élevage: ACTIF, VENDU, MORT, PERDU)
    // Note: Le filtre 'statut' du frontend peut être soit un statut d'élevage (ACTIF, VENDU, etc.)
    // soit un statut de disponibilité (DISPONIBLE, EN_COUPLE, EN_CAGE)
    // On doit gérer les deux cas
    $statutDisponibiliteFilter = null;
    if (isset($filters['statut'])) {
      $statutValue = $filters['statut'];

      // Si c'est un statut de disponibilité (calculé), on le traite après
      if (in_array($statutValue, ['DISPONIBLE', 'EN_COUPLE', 'EN_CAGE'])) {
        $statutDisponibiliteFilter = $statutValue;
      } else {
        // Sinon c'est un statut d'élevage (colonne DB)
        $query->where('statut', $statutValue);
      }
    }

    // Filtre par sexe
    if (isset($filters['sexe'])) {
      $query->where('sexe', $filters['sexe']);
    }

    // Filtre par race
    if (isset($filters['race'])) {
      $query->where('race', $filters['race']);
    }

    // Recherche par bague ou nom
    if (isset($filters['search'])) {
      $search = $filters['search'];
      $query->where(function ($q) use ($search) {
        $q->where('bague', 'like', "%{$search}%")
          ->orWhere('bague_physique', 'like', "%{$search}%")
          ->orWhere('nom', 'like', "%{$search}%");
      });
    }

    // Charger les relations nécessaires pour le calcul du statut
    $pigeons = $query->with(['pere', 'mere', 'cage', 'couplesMale', 'couplesFemelle'])
      ->orderBy('created_at', 'desc')
      ->get();

    // Filtrer par statut de disponibilité si nécessaire (après chargement car c'est un accesseur)
    if ($statutDisponibiliteFilter) {
      $pigeons = $pigeons->filter(function ($pigeon) use ($statutDisponibiliteFilter) {
        return $pigeon->statut_disponibilite === $statutDisponibiliteFilter;
      });
    }

    return $pigeons;
  }

  /**
   * Obtenir les statistiques des pigeons
   * 
   * ⚠️ IMPORTANT : Ne JAMAIS appeler generateBague() ici car ça incrémente le compteur
   * On calcule le prochain numéro manuellement sans incrémenter
   *
   * @param Collection|null $pigeons
   * @param int|null $userId
   * @return array
   */
  public function getStats(?Collection $pigeons = null, int $userId = null): array
  {
    if ($pigeons === null) {
      $pigeons = Pigeon::all();
    }

    // Calcul manuel du prochain numéro sans incrémenter
    $lastBague = Pigeon::withoutGlobalScopes()
      ->where('user_id', $userId)
      ->orderByRaw("CAST(SUBSTRING(bague, 2) AS UNSIGNED) DESC")
      ->value('bague');

    $lastNum = $lastBague ? (int) substr($lastBague, 1) : 0;
    $nextBague = 'P' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);

    // Calculer les statuts de disponibilité dynamiques
    $disponibles = 0;
    $enCouple = 0;
    $enCage = 0;

    foreach ($pigeons as $pigeon) {
      // Le statut_disponibilite est calculé dynamiquement via l'accesseur
      $statutDispo = $pigeon->statut_disponibilite;

      if ($statutDispo === 'DISPONIBLE') {
        $disponibles++;
      } elseif ($statutDispo === 'EN_COUPLE') {
        $enCouple++;
      } elseif ($statutDispo === 'EN_CAGE') {
        $enCage++;
      }
    }

    return [
      'total' => $pigeons->count(),
      'disponibles' => $disponibles,
      'en_couple' => $enCouple,
      'en_cage' => $enCage,
      'males' => $pigeons->where('sexe', 'MALE')->count(),
      'femelles' => $pigeons->where('sexe', 'FEMELLE')->count(),
      'actifs' => $pigeons->where('statut', 'ACTIF')->count(),
      'vendus' => $pigeons->where('statut', 'VENDU')->count(),
      'morts' => $pigeons->where('statut', 'MORT')->count(),
      'perdus' => $pigeons->where('statut', 'PERDU')->count(),
      'last_bague' => $lastBague,
      'next_bague' => $nextBague,
    ];
  }

  /**
   * Créer un nouveau pigeon
   * 
   * Résout les UUIDs en IDs pour les relations (père, mère, cage)
   * Gère l'upload de la photo
   *
   * @param array $data
   * @param int $userId
   * @return Pigeon
   */
  public function createPigeon(array $data, int $userId): Pigeon
  {
    // Générer la bague automatiquement
    $data['bague'] = $this->numberingService->generateBague($userId);
    $data['user_id'] = $userId;
    $data['statut'] = 'ACTIF';
    $data['uuid'] = Str::uuid()->toString();

    // Gérer l'upload de la photo
    if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
      $photo = $data['photo'];
      $filename = 'pigeon_' . $data['uuid'] . '.' . $photo->getClientOriginalExtension();
      $path = $photo->storeAs('pigeons', $filename, 'public');
      $data['photo'] = $path;
    }

    // Résoudre les UUIDs en IDs
    if (isset($data['pere_uuid'])) {
      $data['pere_id'] = Pigeon::withoutGlobalScopes()
        ->where('uuid', $data['pere_uuid'])
        ->value('id');
      unset($data['pere_uuid']);
    }

    if (isset($data['mere_uuid'])) {
      $data['mere_id'] = Pigeon::withoutGlobalScopes()
        ->where('uuid', $data['mere_uuid'])
        ->value('id');
      unset($data['mere_uuid']);
    }

    if (isset($data['cage_uuid'])) {
      $cage = Cage::where('uuid', $data['cage_uuid'])->first();
      if ($cage) {
        $data['cage_id'] = $cage->id;

        // Mettre à jour le statut de la cage en transaction
        DB::transaction(function () use ($cage) {
          $cage->update(['statut' => 'OCCUPE_PIGEON']);
        });
      }
      unset($data['cage_uuid']);
    }

    return Pigeon::create($data);
  }

  /**
   * Mettre à jour un pigeon
   * 
   * Résout les UUIDs en IDs pour les relations
   * Gère l'upload de la photo
   *
   * @param Pigeon $pigeon
   * @param array $data
   * @return Pigeon
   */
  public function updatePigeon(Pigeon $pigeon, array $data): Pigeon
  {
    // Sécurité : ne jamais permettre la modification de la bague et user_id
    unset($data['bague']);
    unset($data['user_id']);

    // Gérer l'upload de la photo
    if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
      // Supprimer l'ancienne photo si elle existe
      if ($pigeon->photo && \Storage::disk('public')->exists($pigeon->photo)) {
        \Storage::disk('public')->delete($pigeon->photo);
      }

      $photo = $data['photo'];
      $filename = 'pigeon_' . $pigeon->uuid . '.' . $photo->getClientOriginalExtension();
      $path = $photo->storeAs('pigeons', $filename, 'public');
      $data['photo'] = $path;
    }

    // Résoudre les UUIDs en IDs
    if (isset($data['pere_uuid'])) {
      $data['pere_id'] = Pigeon::withoutGlobalScopes()
        ->where('uuid', $data['pere_uuid'])
        ->value('id');
      unset($data['pere_uuid']);
    }

    if (isset($data['mere_uuid'])) {
      $data['mere_id'] = Pigeon::withoutGlobalScopes()
        ->where('uuid', $data['mere_uuid'])
        ->value('id');
      unset($data['mere_uuid']);
    }

    if (isset($data['cage_uuid'])) {
      $newCage = Cage::where('uuid', $data['cage_uuid'])->first();
      $oldCageId = $pigeon->cage_id;

      if ($newCage && $newCage->id !== $oldCageId) {
        // Changer de cage en transaction
        DB::transaction(function () use ($pigeon, $newCage, $oldCageId) {
          // Libérer l'ancienne cage si existe
          if ($oldCageId) {
            Cage::find($oldCageId)->update(['statut' => 'LIBRE']);
          }

          // Occuper la nouvelle cage
          $pigeon->update(['cage_id' => $newCage->id]);
          $newCage->update(['statut' => 'OCCUPE_PIGEON']);
        });
      }

      $data['cage_id'] = $newCage ? $newCage->id : null;
      unset($data['cage_uuid']);
    }

    $pigeon->update($data);
    return $pigeon->fresh(['pere', 'mere', 'cage']);
  }

  /**
   * Supprimer un pigeon (soft delete uniquement)
   * 
   * ⚠️ IMPORTANT : On ne supprime JAMAIS physiquement un pigeon
   * Raisons :
   * 1. Traçabilité : historique, généalogie, reproductions restent intacts
   * 2. Numérotation cohérente : NumberingService scanne avec withoutGlobalScopes()
   *    donc il voit les soft-deleted et ne réutilise jamais un numéro
   *
   * @param Pigeon $pigeon
   * @return bool
   */
  public function deletePigeon(Pigeon $pigeon): bool
  {
    // Soft delete uniquement — séquence de bagues préservée
    return $pigeon->delete();
  }

  /**
   * Obtenir la généalogie d'un pigeon
   *
   * @param Pigeon $pigeon
   * @return array
   */
  public function getPigeonGenealogy(Pigeon $pigeon): array
  {
    $pigeon->load(['pere.pere', 'pere.mere', 'mere.pere', 'mere.mere', 'enfants']);

    return [
      'pigeon' => $pigeon,
      'parents' => [
        'pere' => $pigeon->pere,
        'mere' => $pigeon->mere,
      ],
      'grands_parents_paternels' => [
        'grand_pere' => $pigeon->pere?->pere,
        'grand_mere' => $pigeon->pere?->mere,
      ],
      'grands_parents_maternels' => [
        'grand_pere' => $pigeon->mere?->pere,
        'grand_mere' => $pigeon->mere?->mere,
      ],
      'enfants' => $pigeon->enfants,
    ];
  }

  /**
   * Affecter un pigeon à une cage
   * 
   * ⚠️ IMPORTANT : Utiliser une transaction pour synchroniser pigeon.cage_id et cage.statut
   *
   * @param Pigeon $pigeon
   * @param Cage $cage
   * @return Pigeon
   * @throws \Exception
   */
  public function affecterPigeonACage(Pigeon $pigeon, Cage $cage): Pigeon
  {
    // Vérifications
    if ($cage->statut !== 'LIBRE') {
      throw new \Exception('Cette cage n\'est pas libre');
    }

    if ($pigeon->statut !== 'ACTIF') {
      throw new \Exception('Ce pigeon n\'est pas actif');
    }

    if ($pigeon->cage_id) {
      throw new \Exception('Ce pigeon est déjà dans une cage');
    }

    // Affectation en transaction pour garantir la cohérence
    DB::transaction(function () use ($pigeon, $cage) {
      $pigeon->update(['cage_id' => $cage->id]);
      $cage->update(['statut' => 'OCCUPE_PIGEON']);
    });

    return $pigeon->fresh(['cage']);
  }

  /**
   * Libérer un pigeon de sa cage
   * 
   * ⚠️ IMPORTANT : Utiliser une transaction pour synchroniser pigeon.cage_id et cage.statut
   *
   * @param Pigeon $pigeon
   * @return Pigeon
   * @throws \Exception
   */
  public function libererPigeonDeCage(Pigeon $pigeon): Pigeon
  {
    if (!$pigeon->cage_id) {
      throw new \Exception('Ce pigeon n\'est pas dans une cage');
    }

    $cage = $pigeon->cage;

    // Libération en transaction pour garantir la cohérence
    DB::transaction(function () use ($pigeon, $cage) {
      $pigeon->update(['cage_id' => null]);
      $cage->update(['statut' => 'LIBRE']);
    });

    return $pigeon->fresh();
  }

  /**
   * Obtenir les pigeons disponibles pour former un couple
   * 
   * Critères :
   * - Statut ACTIF
   * - Pas dans une cage (cage_id = null)
   * - Pas dans un couple actif
   *
   * @param string $sexe
   * @return Collection
   */
  public function getPigeonsDisponiblesPourCouple(string $sexe): Collection
  {
    return Pigeon::where('sexe', $sexe)
      ->where('statut', 'ACTIF')
      ->whereNull('cage_id') // Pas dans une cage
      ->disponibles() // Pas dans un couple actif
      ->with('cage')
      ->orderBy('bague')
      ->get();
  }

  /**
   * Obtenir TOUS les pigeons pour sélection de parents (même morts/vendus/perdus)
   * 
   * ⚠️ IMPORTANT : Pour la généalogie, on doit pouvoir sélectionner des parents
   * même s'ils sont morts, vendus ou perdus
   *
   * @param string|null $sexe
   * @return Collection
   */
  public function getPigeonsForParents(?string $sexe = null): Collection
  {
    $query = Pigeon::withoutGlobalScopes()
      ->with('cage');

    if ($sexe) {
      $query->where('sexe', $sexe);
    }

    return $query->orderBy('bague')->get();
  }
}
