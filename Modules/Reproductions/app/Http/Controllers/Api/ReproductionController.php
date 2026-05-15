<?php

namespace Modules\Reproductions\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Couples\Models\Couple;
use Modules\Reproductions\Http\Requests\DeclareEclosionRequest;
use Modules\Reproductions\Http\Requests\DeclareSevrageRequest;
use Modules\Reproductions\Http\Requests\EnregistrerPigeonneauxRequest;
use Modules\Reproductions\Http\Requests\StoreReproductionRequest;
use Modules\Reproductions\Http\Requests\UpdateReproductionRequest;
use Modules\Reproductions\Http\Resources\ReproductionResource;
use Modules\Reproductions\Models\Reproduction;
use Modules\Reproductions\Services\ReproductionService;

class ReproductionController extends Controller
{
  public function __construct(
    private ReproductionService $reproductionService
  ) {}

  /**
   * Liste des reproductions avec filtres et stats
   * GET /api/v1/reproductions
   */
  public function index(Request $request): JsonResponse
  {
    try {
      $filters = $request->only(['statut', 'couple_uuid', 'date_debut', 'date_fin', 'search']);

      $reproductions = $this->reproductionService->getAllReproductions($filters);
      $stats = $this->reproductionService->getStats($reproductions);

      return response()->json([
        'success' => true,
        'data' => ReproductionResource::collection($reproductions),
        'meta' => $stats,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erreur lors de la récupération des reproductions',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Détails d'une reproduction
   * GET /api/v1/reproductions/{uuid}
   */
  public function show(string $uuid): JsonResponse
  {
    try {
      $reproduction = Reproduction::where('uuid', $uuid)
        ->with(['couple.male', 'couple.femelle', 'couple.cage'])
        ->firstOrFail();

      return response()->json([
        'success' => true,
        'data' => new ReproductionResource($reproduction),
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Reproduction introuvable',
      ], 404);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erreur lors de la récupération de la reproduction',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Créer une nouvelle reproduction (ponte)
   * POST /api/v1/reproductions
   */
  public function store(StoreReproductionRequest $request): JsonResponse
  {
    try {
      $reproduction = $this->reproductionService->createReproduction($request->validated());

      // Recharger les relations pour la réponse
      $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);

      return response()->json([
        'success' => true,
        'message' => 'Ponte enregistrée avec succès',
        'data' => new ReproductionResource($reproduction),
      ], 201);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
      ], 422);
    }
  }

  /**
   * Mettre à jour une reproduction
   * PUT /api/v1/reproductions/{uuid}
   */
  public function update(UpdateReproductionRequest $request, string $uuid): JsonResponse
  {
    try {
      $reproduction = Reproduction::where('uuid', $uuid)->firstOrFail();

      $reproduction = $this->reproductionService->updateReproduction(
        $reproduction,
        $request->validated()
      );

      // Recharger les relations pour la réponse
      $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);

      return response()->json([
        'success' => true,
        'message' => 'Reproduction modifiée avec succès',
        'data' => new ReproductionResource($reproduction),
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Reproduction introuvable',
      ], 404);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
      ], 422);
    }
  }

  /**
   * Supprimer une reproduction (soft delete)
   * DELETE /api/v1/reproductions/{uuid}
   */
  public function destroy(string $uuid): JsonResponse
  {
    try {
      $reproduction = Reproduction::where('uuid', $uuid)->firstOrFail();

      $this->reproductionService->deleteReproduction($reproduction);

      return response()->json([
        'success' => true,
        'message' => 'Reproduction supprimée avec succès',
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Reproduction introuvable',
      ], 404);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
      ], 422);
    }
  }

  /**
   * Déclarer l'éclosion
   * POST /api/v1/reproductions/{uuid}/eclosion
   */
  public function declareEclosion(DeclareEclosionRequest $request, string $uuid): JsonResponse
  {
    try {
      $reproduction = Reproduction::where('uuid', $uuid)->firstOrFail();

      $reproduction = $this->reproductionService->declareEclosion(
        $reproduction,
        $request->validated()
      );

      // Recharger les relations pour la réponse
      $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);

      return response()->json([
        'success' => true,
        'message' => 'Éclosion déclarée avec succès',
        'data' => new ReproductionResource($reproduction),
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Reproduction introuvable',
      ], 404);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
      ], 422);
    }
  }

  /**
   * Déclarer le sevrage
   * POST /api/v1/reproductions/{uuid}/sevrage
   */
  public function declareSevrage(DeclareSevrageRequest $request, string $uuid): JsonResponse
  {
    try {
      $reproduction = Reproduction::where('uuid', $uuid)->firstOrFail();

      $reproduction = $this->reproductionService->declareSevrage(
        $reproduction,
        $request->validated()
      );

      // Recharger les relations pour la réponse
      $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);

      return response()->json([
        'success' => true,
        'message' => 'Sevrage déclaré avec succès',
        'data' => new ReproductionResource($reproduction),
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Reproduction introuvable',
      ], 404);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
      ], 422);
    }
  }

  /**
   * Enregistrer les pigeonneaux
   * POST /api/v1/reproductions/{uuid}/enregistrer-pigeonneaux
   */
  public function enregistrerPigeonneaux(EnregistrerPigeonneauxRequest $request, string $uuid): JsonResponse
  {
    try {
      $reproduction = Reproduction::where('uuid', $uuid)->firstOrFail();

      $reproduction = $this->reproductionService->enregistrerPigeonneaux(
        $reproduction,
        $request->validated()['pigeonneaux']
      );

      // Recharger les relations pour la réponse
      $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);

      return response()->json([
        'success' => true,
        'message' => 'Pigeonneaux enregistrés avec succès',
        'data' => new ReproductionResource($reproduction),
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Reproduction introuvable',
      ], 404);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
      ], 422);
    }
  }

  /**
   * Liste des couples actifs pour nouvelle reproduction
   * GET /api/v1/reproductions/couples/actifs
   */
  public function couplesActifs(): JsonResponse
  {
    try {
      $couples = $this->reproductionService->getCouplesActifs();

      return response()->json([
        'success' => true,
        'data' => $couples,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erreur lors de la récupération des couples actifs',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Reproductions d'un couple
   * GET /api/v1/couples/{uuid}/reproductions
   */
  public function reproductionsByCouple(string $coupleUuid): JsonResponse
  {
    try {
      $couple = Couple::where('uuid', $coupleUuid)->firstOrFail();

      $reproductions = $this->reproductionService->getReproductionsByCouple($couple->id);

      return response()->json([
        'success' => true,
        'data' => ReproductionResource::collection($reproductions),
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Couple introuvable',
      ], 404);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erreur lors de la récupération des reproductions',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
}
