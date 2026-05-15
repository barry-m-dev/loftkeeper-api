<?php

namespace Modules\Couples\Http\Controllers\Api;

use App\Core\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Couples\Http\Requests\StoreCoupleRequest;
use Modules\Couples\Http\Requests\UpdateCoupleRequest;
use Modules\Couples\Http\Resources\CoupleResource;
use Modules\Couples\Models\Couple;
use Modules\Couples\Services\CoupleService;
use Modules\Pigeons\Http\Resources\PigeonResource;
use Modules\Cages\Http\Resources\CageResource;

/**
 * Controller pour la gestion des couples
 * Orchestrateur - Délègue la logique métier au CoupleService
 * 
 * @package Modules\Couples\Http\Controllers\Api
 */
class CoupleController extends Controller
{
  use ApiResponse, AuthorizesRequests;

  protected CoupleService $coupleService;

  /**
   * Constructeur
   * 
   * @param CoupleService $coupleService
   */
  public function __construct(CoupleService $coupleService)
  {
    $this->coupleService = $coupleService;
  }

  /**
   * Liste des couples avec filtres
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function index(Request $request): JsonResponse
  {
    try {
      $filters = $request->only(['statut', 'avec_cage', 'search']);
      $couples = $this->coupleService->getAllCouples($filters);
      $stats = $this->coupleService->getStats($couples, auth()->id());

      return $this->success([
        'data' => CoupleResource::collection($couples)->resolve(),
        'meta' => $stats,
      ]);
    } catch (\Exception $e) {
      \Log::error('Erreur index couples', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return $this->error(
        'Erreur lors de la récupération des couples: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Détail d'un couple
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function show(string $uuid): JsonResponse
  {
    try {
      $couple = Couple::where('uuid', $uuid)
        ->where('user_id', auth()->id())
        ->with(['male', 'femelle', 'cage', 'reproductions'])
        ->firstOrFail();

      return $this->success([
        'data' => (new CoupleResource($couple))->resolve(),
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Couple non trouvé', 404);
    } catch (\Exception $e) {
      \Log::error('Erreur show couple', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération du couple: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Créer un couple
   * 
   * @param StoreCoupleRequest $request
   * @return JsonResponse
   */
  public function store(StoreCoupleRequest $request): JsonResponse
  {
    try {
      $couple = $this->coupleService->createCouple(
        $request->validated(),
        auth()->id()
      );

      return $this->success(
        new CoupleResource($couple),
        'Couple créé avec succès',
        201
      );
    } catch (\Exception $e) {
      \Log::error('Erreur création couple', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return $this->error(
        $e->getMessage(),
        422
      );
    }
  }

  /**
   * Modifier un couple
   * 
   * @param UpdateCoupleRequest $request
   * @param string $uuid
   * @return JsonResponse
   */
  public function update(UpdateCoupleRequest $request, string $uuid): JsonResponse
  {
    try {
      $couple = Couple::where('uuid', $uuid)
        ->where('user_id', auth()->id())
        ->firstOrFail();

      $updatedCouple = $this->coupleService->updateCouple(
        $couple,
        $request->validated()
      );

      return $this->success(
        new CoupleResource($updatedCouple),
        'Couple mis à jour avec succès'
      );
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Couple non trouvé', 404);
    } catch (\Exception $e) {
      \Log::error('Erreur update couple', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        $e->getMessage(),
        422
      );
    }
  }

  /**
   * Supprimer un couple
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function destroy(string $uuid): JsonResponse
  {
    try {
      $couple = Couple::where('uuid', $uuid)
        ->where('user_id', auth()->id())
        ->firstOrFail();

      $this->coupleService->deleteCouple($couple);

      return $this->success(null, 'Couple supprimé avec succès');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Couple non trouvé', 404);
    } catch (\Exception $e) {
      \Log::error('Erreur delete couple', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        $e->getMessage(),
        422
      );
    }
  }

  /**
   * Rompre un couple
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function rompre(string $uuid): JsonResponse
  {
    try {
      $couple = Couple::where('uuid', $uuid)
        ->where('user_id', auth()->id())
        ->firstOrFail();

      $rompu = $this->coupleService->rompre($couple);

      return $this->success(
        new CoupleResource($rompu),
        'Couple rompu avec succès'
      );
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Couple non trouvé', 404);
    } catch (\Exception $e) {
      \Log::error('Erreur rupture couple', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        $e->getMessage(),
        422
      );
    }
  }

  /**
   * Mâles disponibles pour couple
   * 
   * @return JsonResponse
   */
  public function malesDisponibles(): JsonResponse
  {
    try {
      $males = $this->coupleService->getMalesDisponibles(auth()->id());

      return $this->success([
        'data' => PigeonResource::collection($males)->resolve(),
      ]);
    } catch (\Exception $e) {
      \Log::error('Erreur males disponibles', [
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération des mâles disponibles',
        500
      );
    }
  }

  /**
   * Femelles disponibles pour couple
   * 
   * @return JsonResponse
   */
  public function femellesDisponibles(): JsonResponse
  {
    try {
      $femelles = $this->coupleService->getFemellesDisponibles(auth()->id());

      return $this->success([
        'data' => PigeonResource::collection($femelles)->resolve(),
      ]);
    } catch (\Exception $e) {
      \Log::error('Erreur femelles disponibles', [
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération des femelles disponibles',
        500
      );
    }
  }

  /**
   * Historique des reproductions d'un couple
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function reproductions(string $uuid): JsonResponse
  {
    try {
      $couple = Couple::where('uuid', $uuid)
        ->where('user_id', auth()->id())
        ->firstOrFail();

      $historique = $this->coupleService->getReproductionsHistorique($couple);

      return $this->success($historique);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Couple non trouvé', 404);
    } catch (\Exception $e) {
      \Log::error('Erreur reproductions couple', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération des reproductions',
        500
      );
    }
  }
}
