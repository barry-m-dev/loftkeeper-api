<?php

namespace Modules\Couples\Services;

use Modules\Couples\Models\Couple;
use Modules\Pigeons\Models\Pigeon;
use Modules\Cages\Models\Cage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\NumberingService;

/**
 * Service pour la gestion de la logique métier des couples
 * 
 * @package Modules\Couples\Services
 */
class CoupleService
{
  protected NumberingService $numberingService;

  public function __construct(NumberingService $numberingService)
  {
    $this->numberingService = $numberingService;
  }

  /**
   * Récupérer tous les couples avec filtres
   *
   * @param array $filters
   * @return Collection
   */
  public function getAllCouples(array $filters = []): Collection
  {
    $query = Couple::query()->with(['male', 'femelle', 'cage', 'reproductions']);

    // Filtre par statut
    if (isset($filters['statut'])) {
      $query->where('statut', $filters['statut']);
    }

    // Filtre par affectation cage
    if (isset($filters['avec_cage'])) {
      if ($filters['avec_cage'] === 'true' || $filters['avec_cage'] === true) {
        $query->whereNotNull('cage_id');
      } else {
        $query->whereNull('cage_id');
      }
    }

    // Recherche par code ou bagues
    if (isset($filters['search'])) {
      $search = $filters['search'];
      $query->where(function ($q) use ($search) {
        $q->where('code', 'like', "%{$search}%")
          ->orWhereHas('male', function ($q) use ($search) {
            $q->where('bague', 'like', "%{$search}%");
          })
          ->orWhereHas('femelle', function ($q) use ($search) {
            $q->where('bague', 'like', "%{$search}%");
          });
      });
    }

    return $query->orderByRaw("CAST(SUBSTRING(code, 2) AS UNSIGNED) DESC")->get();
  }

  /**
   * Obtenir les statistiques des couples
   * 
   * ⚠️ IMPORTANT : Ne JAMAIS appeler generateCoupleCode() ici car ça incrémente le compteur
   * On calcule le prochain code manuellement sans incrémenter
   *
   * @param Collection|null $couples
   * @param int|null $userId
   * @return array
   */
  public function getStats(?Collection $couples = null, int $userId = null): array
  {
    if ($couples === null) {
      $couples = Couple::where('user_id', $userId)->get();
    }

    // Calcul manuel du prochain code sans incrémenter
    $lastCode = Couple::withoutGlobalScopes()
      ->where('user_id', $userId)
      ->orderByRaw("CAST(SUBSTRING(code, 2) AS UNSIGNED) DESC")
      ->value('code');

    $lastNum = $lastCode ? (int) substr($lastCode, 1) : 0;
    $nextCode = 'C' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);

    return [
      'total' => $couples->count(),
      'actifs' => $couples->where('statut', 'ACTIF')->count(),
      'rompus' => $couples->where('statut', 'ROMPU')->count(),
      'avec_cage' => $couples->whereNotNull('cage_id')->count(),
      'sans_cage' => $couples->whereNull('cage_id')->count(),
      'last_code' => $lastCode,
      'next_code' => $nextCode,
    ];
  }

  /**
   * Créer un nouveau couple
   * 
   * Résout les UUIDs en IDs pour les relations
   * Applique toutes les règles métier RG-C01 à RG-C08
   *
   * @param array $data
   * @param int $userId
   * @return Couple
   */
  public function createCouple(array $data, int $userId): Couple
  {
    // Résoudre les UUIDs en IDs
    $male = Pigeon::where('uuid', $data['male_uuid'])
      ->where('user_id', $userId)
      ->firstOrFail();

    $femelle = Pigeon::where('uuid', $data['femelle_uuid'])
      ->where('user_id', $userId)
      ->firstOrFail();

    // Vérifications métier (RG-C01 à RG-C04)
    $this->validateCoupleCreation($male, $femelle);

    // Résoudre cage_uuid si fourni (RG-C08)
    $cageId = null;
    if (isset($data['cage_uuid']) && $data['cage_uuid'] !== null) {
      $cage = Cage::where('uuid', $data['cage_uuid'])
        ->where('user_id', $userId)
        ->firstOrFail();

      if ($cage->statut !== 'LIBRE') {
        throw new \Exception('Cette cage n\'est pas libre');
      }

      $cageId = $cage->id;
    }

    // Transaction pour garantir la cohérence
    return DB::transaction(function () use ($data, $male, $femelle, $cageId, $userId) {
      // RG-C06 : Générer le code automatiquement
      $code = $this->numberingService->generateCoupleCode($userId);

      // Libérer la cage individuelle du mâle s'il y en a une
      if ($male->cage_id) {
        Cage::where('id', $male->cage_id)->update(['statut' => 'LIBRE']);
        $male->update(['cage_id' => null]);
      }

      // Libérer la cage individuelle de la femelle si elle y en a une
      if ($femelle->cage_id) {
        Cage::where('id', $femelle->cage_id)->update(['statut' => 'LIBRE']);
        $femelle->update(['cage_id' => null]);
      }

      // Créer le couple
      $couple = Couple::create([
        'uuid' => \Str::uuid()->toString(),
        'code' => $code,
        'male_id' => $male->id,
        'femelle_id' => $femelle->id,
        'cage_id' => $cageId,
        'date_formation' => $data['date_formation'] ?? now()->toDateString(), // RG-C07
        'statut' => 'ACTIF',
        'user_id' => $userId,
        'notes' => $data['notes'] ?? null,
      ]);

      // Mettre à jour la cage si affectée
      if ($cageId) {
        Cage::where('id', $cageId)->update([
          'statut' => 'OCCUPE_COUPLE'
        ]);
      }

      return $couple->load(['male', 'femelle', 'cage']);
    });
  }

  /**
   * Valider la création d'un couple
   * Applique les règles RG-C01 à RG-C04
   *
   * @param Pigeon $male
   * @param Pigeon $femelle
   * @return void
   * @throws \Exception
   */
  protected function validateCoupleCreation(Pigeon $male, Pigeon $femelle): void
  {
    // RG-C01 : Sexes opposés obligatoires
    if ($male->sexe !== 'MALE') {
      throw new \Exception('Le premier pigeon doit être un mâle');
    }
    if ($femelle->sexe !== 'FEMELLE') {
      throw new \Exception('Le second pigeon doit être une femelle');
    }

    // RG-C02 : Pigeons actifs uniquement
    if ($male->statut !== 'ACTIF') {
      throw new \Exception("Le mâle {$male->bague} n'est pas actif");
    }
    if ($femelle->statut !== 'ACTIF') {
      throw new \Exception("La femelle {$femelle->bague} n'est pas active");
    }

    // RG-C03 : Pigeons disponibles (pas déjà en couple)
    $maleEnCouple = Couple::where('statut', 'ACTIF')
      ->where(function ($q) use ($male) {
        $q->where('male_id', $male->id)
          ->orWhere('femelle_id', $male->id);
      })
      ->exists();

    if ($maleEnCouple) {
      throw new \Exception("Le mâle {$male->bague} est déjà dans un couple actif");
    }

    $femelleEnCouple = Couple::where('statut', 'ACTIF')
      ->where(function ($q) use ($femelle) {
        $q->where('male_id', $femelle->id)
          ->orWhere('femelle_id', $femelle->id);
      })
      ->exists();

    if ($femelleEnCouple) {
      throw new \Exception("La femelle {$femelle->bague} est déjà dans un couple actif");
    }

    // RG-C04 : Pas d'auto-union
    if ($male->id === $femelle->id) {
      throw new \Exception('Un pigeon ne peut pas former un couple avec lui-même');
    }
  }

  /**
   * Modifier un couple
   * Applique les règles RG-C09 à RG-C12
   *
   * @param Couple $couple
   * @param array $data
   * @return Couple
   */
  public function updateCouple(Couple $couple, array $data): Couple
  {
    return DB::transaction(function () use ($couple, $data) {
      $updates = [];

      // RG-C12 : Modification des notes (toujours possible même si ROMPU)
      // On traite les notes en premier pour pouvoir court-circuiter les autres modifs si ROMPU
      if (isset($data['notes'])) {
        $updates['notes'] = $data['notes'];
      }

      // Si le couple est ROMPU, on ne permet QUE la modification des notes
      if ($couple->statut !== 'ACTIF') {
        if (count($data) > 1 || (count($data) === 1 && !isset($data['notes']))) {
          throw new \Exception('Impossible de modifier un couple rompu (sauf les notes)');
        }

        if (!empty($updates)) {
          $couple->update($updates);
        }
        return $couple->fresh(['male', 'femelle', 'cage']);
      }

      // Remplacement du mâle (RG-C10)
      if (isset($data['male_uuid'])) {
        $newMale = Pigeon::where('uuid', $data['male_uuid'])
          ->where('user_id', $couple->user_id)
          ->firstOrFail();

        // Ne valider que si le mâle a changé
        if ($newMale->id !== $couple->male_id) {
          $this->validatePigeonReplacement($newMale, 'MALE', $couple, 'male');
          
          // Libérer la cage individuelle du nouveau mâle
          if ($newMale->cage_id) {
            Cage::where('id', $newMale->cage_id)->update(['statut' => 'LIBRE']);
            $newMale->update(['cage_id' => null]);
          }

          $updates['male_id'] = $newMale->id;
        }
      }

      // Remplacement de la femelle (RG-C10)
      if (isset($data['femelle_uuid'])) {
        $newFemelle = Pigeon::where('uuid', $data['femelle_uuid'])
          ->where('user_id', $couple->user_id)
          ->firstOrFail();

        // Ne valider que si la femelle a changé
        if ($newFemelle->id !== $couple->femelle_id) {
          $this->validatePigeonReplacement($newFemelle, 'FEMELLE', $couple, 'femelle');
          
          // Libérer la cage individuelle de la nouvelle femelle
          if ($newFemelle->cage_id) {
            Cage::where('id', $newFemelle->cage_id)->update(['statut' => 'LIBRE']);
            $newFemelle->update(['cage_id' => null]);
          }

          $updates['femelle_id'] = $newFemelle->id;
        }
      }

      // Modification de la cage (RG-C11)
      if (array_key_exists('cage_uuid', $data)) {
        if ($data['cage_uuid'] === null) {
          // Retirer de la cage
          if ($couple->cage_id) {
            Cage::where('id', $couple->cage_id)->update(['statut' => 'LIBRE']);
          }
          $updates['cage_id'] = null;
        } else {
          // Changer de cage
          $newCage = Cage::where('uuid', $data['cage_uuid'])
            ->where('user_id', $couple->user_id)
            ->firstOrFail();

          // Ne valider que si la cage a changé
          if ($newCage->id !== $couple->cage_id) {
            if ($newCage->statut !== 'LIBRE') {
              throw new \Exception('Cette cage n\'est pas libre');
            }

            // Libérer l'ancienne cage
            if ($couple->cage_id) {
              Cage::where('id', $couple->cage_id)->update(['statut' => 'LIBRE']);
            }

            // Occuper la nouvelle cage
            $newCage->update(['statut' => 'OCCUPE_COUPLE']);
            $updates['cage_id'] = $newCage->id;
          }
        }
      }

      // Modification de la date de formation
      if (isset($data['date_formation'])) {
        $updates['date_formation'] = $data['date_formation'];
      }

      // RG-C12 : Modification des notes (déjà traitée plus haut si ROMPU)
      // Mais on la laisse ici pour la cohérence en mode ACTIF
      if (isset($data['notes'])) {
        $updates['notes'] = $data['notes'];
      }

      // Appliquer les modifications
      if (!empty($updates)) {
        $couple->update($updates);
      }

      return $couple->fresh(['male', 'femelle', 'cage']);
    });
  }

  /**
   * Valider le remplacement d'un pigeon dans un couple
   * Applique la règle RG-C10
   *
   * @param Pigeon $newPigeon
   * @param string $expectedSexe
   * @param Couple $couple
   * @param string $role
   * @return void
   * @throws \Exception
   */
  protected function validatePigeonReplacement(
    Pigeon $newPigeon,
    string $expectedSexe,
    Couple $couple,
    string $role
  ): void {
    // RG-C10 : Vérifier le sexe
    if ($newPigeon->sexe !== $expectedSexe) {
      throw new \Exception("Le pigeon doit être de sexe {$expectedSexe}");
    }

    // RG-C10 : Vérifier que ce n'est pas le même pigeon
    $currentPigeonId = $role === 'male' ? $couple->male_id : $couple->femelle_id;
    if ($newPigeon->id === $currentPigeonId) {
      throw new \Exception('Ce pigeon est déjà dans le couple');
    }

    // RG-C10 : Vérifier que le pigeon n'est pas l'autre membre du couple
    $otherPigeonId = $role === 'male' ? $couple->femelle_id : $couple->male_id;
    if ($newPigeon->id === $otherPigeonId) {
      throw new \Exception('Ce pigeon est déjà l\'autre membre du couple');
    }

    // RG-C10 : Vérifier que le pigeon est actif
    if ($newPigeon->statut !== 'ACTIF') {
      throw new \Exception("Le pigeon {$newPigeon->bague} n'est pas actif");
    }

    // RG-C10 : Vérifier que le pigeon n'est pas déjà en couple
    $enCouple = Couple::where('statut', 'ACTIF')
      ->where('id', '!=', $couple->id)
      ->where(function ($q) use ($newPigeon) {
        $q->where('male_id', $newPigeon->id)
          ->orWhere('femelle_id', $newPigeon->id);
      })
      ->exists();

    if ($enCouple) {
      throw new \Exception("Le pigeon {$newPigeon->bague} est déjà dans un autre couple actif");
    }
  }

  /**
   * Rompre un couple
   * Applique les règles RG-C13 à RG-C15
   *
   * @param Couple $couple
   * @return Couple
   */
  public function rompre(Couple $couple): Couple
  {
    // RG-C13 : Rupture uniquement si ACTIF
    if ($couple->statut !== 'ACTIF') {
      throw new \Exception('Ce couple est déjà rompu');
    }

    return DB::transaction(function () use ($couple) {
      // Sauvegarder l'ID de la cage avant de mettre à null dans le couple
      $cageId = $couple->cage_id;

      // RG-C14 : Effets de la rupture
      $couple->update([
        'statut' => 'ROMPU',
        'date_rupture' => now()->toDateString(),
        'cage_id' => null,
      ]);

      // Libérer la cage si elle était affectée
      if ($cageId) {
        Cage::where('id', $cageId)->update(['statut' => 'LIBRE']);
      }

      // RG-C15 : Conservation de l'historique (soft delete, pas de suppression)
      return $couple->fresh(['male', 'femelle', 'cage']);
    });
  }

  /**
   * Supprimer un couple (soft delete)
   * Applique les règles RG-C16 à RG-C18
   *
   * @param Couple $couple
   * @return void
   */
  public function deleteCouple(Couple $couple): void
  {
    // RG-C17 : Interdire la suppression si reproductions
    if ($couple->reproductions()->exists()) {
      throw new \Exception(
        'Impossible de supprimer un couple ayant des reproductions. Vous pouvez le rompre.'
      );
    }

    DB::transaction(function () use ($couple) {
      // RG-C18 : Libérer la cage si affectée
      if ($couple->cage_id) {
        Cage::where('id', $couple->cage_id)->update(['statut' => 'LIBRE']);
      }

      // RG-C16 : Soft delete uniquement
      $couple->delete();
    });
  }

  /**
   * Récupérer les pigeons mâles disponibles pour couple
   *
   * @param int $userId
   * @return Collection
   */
  public function getMalesDisponibles(int $userId): Collection
  {
    return Pigeon::where('user_id', $userId)
      ->where('sexe', 'MALE')
      ->where('statut', 'ACTIF')
      ->whereNotIn('id', function ($query) {
        $query->select('male_id')
          ->from('couples')
          ->where('statut', 'ACTIF')
          ->whereNull('deleted_at');
      })
      ->whereNotIn('id', function ($query) {
        $query->select('femelle_id')
          ->from('couples')
          ->where('statut', 'ACTIF')
          ->whereNull('deleted_at');
      })
      ->orderBy('bague', 'asc')
      ->get();
  }

  /**
   * Récupérer les pigeons femelles disponibles pour couple
   *
   * @param int $userId
   * @return Collection
   */
  public function getFemellesDisponibles(int $userId): Collection
  {
    return Pigeon::where('user_id', $userId)
      ->where('sexe', 'FEMELLE')
      ->where('statut', 'ACTIF')
      ->whereNotIn('id', function ($query) {
        $query->select('male_id')
          ->from('couples')
          ->where('statut', 'ACTIF')
          ->whereNull('deleted_at');
      })
      ->whereNotIn('id', function ($query) {
        $query->select('femelle_id')
          ->from('couples')
          ->where('statut', 'ACTIF')
          ->whereNull('deleted_at');
      })
      ->orderBy('bague', 'asc')
      ->get();
  }

  /**
   * Récupérer les cages libres pour affectation
   *
   * @param int $userId
   * @return Collection
   */
  public function getCagesLibres(int $userId): Collection
  {
    return Cage::where('user_id', $userId)
      ->where('statut', 'LIBRE')
      ->orderBy('numero', 'asc')
      ->get();
  }

  /**
   * Récupérer l'historique des reproductions d'un couple
   *
   * @param Couple $couple
   * @return array
   */
  public function getReproductionsHistorique(Couple $couple): array
  {
    $reproductions = $couple->reproductions()
      ->orderBy('date_ponte', 'desc')
      ->get();

    return [
      'total_pontes' => $reproductions->count(),
      'total_eclosions' => $reproductions->whereIn('statut', ['ECLOSION', 'SEVRAGE', 'ENREGISTRE'])->count(),
      'total_pigeonneaux' => $reproductions->sum('nb_pigeonneaux'),
      'total_echecs' => $reproductions->where('statut', 'ECHEC')->count(),
      'reproductions' => $reproductions,
    ];
  }
}
