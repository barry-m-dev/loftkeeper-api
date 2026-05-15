<?php

namespace Modules\Cages\Http\Controllers\Api;

use App\Core\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Cages\Http\Requests\StoreCageRequest;
use Modules\Cages\Http\Requests\UpdateCageRequest;
use Modules\Cages\Http\Resources\CageResource;
use Modules\Cages\Models\Cage;
use Modules\Cages\Services\CageService;
use Modules\Pigeons\Models\Pigeon;
use Modules\Couples\Models\Couple;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller pour la gestion des cages
 * Orchestrateur - Délègue la logique métier au CageService
 * 
 * @package Modules\Cages\Http\Controllers\Api
 */
class CageController extends Controller
{
  use ApiResponse, AuthorizesRequests;

  protected CageService $cageService;

  /**
   * Constructeur
   * 
   * @param CageService $cageService
   */
  public function __construct(CageService $cageService)
  {
    $this->cageService = $cageService;
  }

  /**
   * Liste des cages avec filtres
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function index(Request $request): JsonResponse
  {
    try {
      $filters = $request->only(['statut', 'search']);
      $cages = $this->cageService->getAllCages($filters);
      $stats = $this->cageService->getStats($cages, auth()->id());

      \Log::info('Cages index', [
        'filters' => $filters,
        'cages_count' => $cages->count(),
        'stats' => $stats,
      ]);

      return $this->success([
        'data' => CageResource::collection($cages)->resolve(),
        'meta' => $stats,
      ]);
    } catch (\Exception $e) {
      \Log::error('Erreur index cages', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return $this->error(
        'Erreur lors de la récupération des cages: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Grille complète pour visualisation
   * 
   * @return JsonResponse
   */
  public function grille(): JsonResponse
  {
    try {
      $cages = $this->cageService->getAllCages();
      return $this->success(CageResource::collection($cages)->resolve());
    } catch (\Exception $e) {
      return $this->error(
        'Erreur lors de la récupération de la grille: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Créer une cage
   * 
   * @param StoreCageRequest $request
   * @return JsonResponse
   */
  public function store(StoreCageRequest $request): JsonResponse
  {
    try {
      $cage = $this->cageService->createCage(
        $request->validated(),
        auth()->id()
      );

      return $this->success(
        new CageResource($cage),
        'Cage créée avec succès',
        201
      );
    } catch (\Exception $e) {
      return $this->error(
        'Erreur lors de la création de la cage: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Détail d'une cage
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function show(string $uuid): JsonResponse
  {
    try {
      $cage = Cage::where('uuid', $uuid)
        ->with(['pigeon', 'couple.male', 'couple.femelle', 'couple.reproductions'])
        ->firstOrFail();

      return $this->success(new CageResource($cage));
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Cage non trouvée', 404);
    } catch (\Exception $e) {
      return $this->error(
        'Erreur lors de la récupération de la cage: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Modifier une cage
   * 
   * @param UpdateCageRequest $request
   * @param string $uuid
   * @return JsonResponse
   */
  public function update(UpdateCageRequest $request, string $uuid): JsonResponse
  {
    try {
      $cage = Cage::where('uuid', $uuid)->firstOrFail();

      $updatedCage = $this->cageService->updateCage(
        $cage,
        $request->validated()
      );

      return $this->success(
        new CageResource($updatedCage),
        'Cage mise à jour avec succès'
      );
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Cage non trouvée', 404);
    } catch (\Exception $e) {
      return $this->error(
        'Erreur lors de la mise à jour de la cage: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Supprimer une cage (seulement si LIBRE)
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function destroy(string $uuid): JsonResponse
  {
    try {
      $cage = Cage::where('uuid', $uuid)->firstOrFail();

      // Vérification explicite du statut LIBRE
      if ($cage->statut !== 'LIBRE') {
        return $this->error(
          'Impossible de supprimer une cage occupée. Veuillez d\'abord la libérer.',
          400
        );
      }

      $this->cageService->deleteCage($cage);

      return $this->success(null, 'Cage supprimée avec succès');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Cage non trouvée', 404);
    } catch (\Exception $e) {
      return $this->error(
        substr($e->getMessage(), 0, 300),
        422
      );
    }
  }

  /**
   * Affecter un pigeon à une cage
   * 
   * @param Request $request
   * @param string $uuid
   * @return JsonResponse
   */
  public function affecterPigeon(Request $request, string $uuid): JsonResponse
  {
    try {
      $request->validate([
        'pigeon_uuid' => 'required|string|exists:pigeons,uuid',
      ]);

      $cage = Cage::where('uuid', $uuid)->firstOrFail();
      $pigeon = Pigeon::where('uuid', $request->pigeon_uuid)
        ->where('user_id', auth()->id())
        ->firstOrFail();

      $updatedCage = $this->cageService->affecterPigeon($cage, $pigeon);

      return $this->success(
        new CageResource($updatedCage),
        'Pigeon affecté à la cage avec succès'
      );
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Cage ou pigeon non trouvé', 404);
    } catch (\Exception $e) {
      return $this->error(
        substr($e->getMessage(), 0, 300),
        422
      );
    }
  }

  /**
   * Affecter un couple à une cage
   * 
   * @param Request $request
   * @param string $uuid
   * @return JsonResponse
   */
  public function affecterCouple(Request $request, string $uuid): JsonResponse
  {
    try {
      $request->validate([
        'couple_uuid' => 'required|string|exists:couples,uuid',
      ]);

      $cage = Cage::where('uuid', $uuid)->firstOrFail();
      $couple = Couple::where('uuid', $request->couple_uuid)
        ->where('user_id', auth()->id())
        ->firstOrFail();

      $updatedCage = $this->cageService->affecterCouple($cage, $couple);

      return $this->success(
        new CageResource($updatedCage),
        'Couple affecté à la cage avec succès'
      );
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Cage ou couple non trouvé', 404);
    } catch (\Exception $e) {
      return $this->error(
        substr($e->getMessage(), 0, 300),
        422
      );
    }
  }

  /**
   * Libérer une cage
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function liberer(string $uuid): JsonResponse
  {
    try {
      $cage = Cage::where('uuid', $uuid)->firstOrFail();

      $updatedCage = $this->cageService->liberer($cage);

      return $this->success(
        new CageResource($updatedCage),
        'Cage libérée avec succès'
      );
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Cage non trouvée', 404);
    } catch (\Exception $e) {
      return $this->error(
        substr($e->getMessage(), 0, 300),
        422
      );
    }
  }

  /**
   * Récupérer les couples disponibles pour affectation à une cage
   * Retourne uniquement les couples ACTIFS sans cage
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function couplesDisponibles(Request $request): JsonResponse
  {
    try {
      // UUID de la cage en cours d'édition (optionnel)
      $cageUuid = $request->query('cage_uuid');
      $cageId = null;

      if ($cageUuid) {
        $cage = Cage::where('uuid', $cageUuid)
          ->where('user_id', auth()->id())
          ->first();
        $cageId = $cage?->id;
      }

      // Récupérer les couples disponibles
      $couples = Couple::where('user_id', auth()->id())
        ->where('statut', 'ACTIF')
        ->where(function ($query) use ($cageId) {
          $query->whereNull('cage_id');
          // En mode édition, inclure le couple actuellement affecté à cette cage
          if ($cageId) {
            $query->orWhere('cage_id', $cageId);
          }
        })
        ->with(['male', 'femelle'])
        ->orderBy('code', 'asc')
        ->get();

      return $this->success([
        'data' => \Modules\Couples\Http\Resources\CoupleResource::collection($couples)->resolve(),
      ]);
    } catch (\Exception $e) {
      \Log::error('Erreur couples disponibles', [
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération des couples disponibles',
        500
      );
    }
  }
}
