<?php

namespace Modules\Couples\Http\Controllers\Api;

use App\Core\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Couples\Http\Resources\CoupleResource;
use Modules\Couples\Models\Couple;

/**
 * Controller pour la gestion des couples
 * 
 * @package Modules\Couples\Http\Controllers\Api
 */
class CoupleController extends Controller
{
  use ApiResponse, AuthorizesRequests;

  /**
   * Liste des couples avec filtres
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function index(Request $request): JsonResponse
  {
    try {
      $query = Couple::query()
        ->with(['male', 'femelle', 'cage'])
        ->where('user_id', auth()->id());

      // Filtre par statut
      if ($request->has('statut')) {
        $query->where('statut', $request->statut);

        // Si on demande les couples ACTIFS, filtrer aussi ceux sans cage
        if ($request->statut === 'ACTIF') {
          $query->whereNull('cage_id');
        }
      }

      // Recherche par code
      if ($request->has('search')) {
        $search = $request->search;
        $query->where('code', 'like', "%{$search}%");
      }

      $couples = $query->orderBy('created_at', 'desc')->get();

      // Calculer les stats
      $stats = [
        'total' => $couples->count(),
        'actifs' => $couples->where('statut', 'ACTIF')->count(),
        'rompus' => $couples->where('statut', 'ROMPU')->count(),
        'avec_cage' => $couples->whereNotNull('cage_id')->count(),
        'sans_cage' => $couples->whereNull('cage_id')->count(),
      ];

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
   * @param Request $request
   * @return JsonResponse
   */
  public function store(Request $request): JsonResponse
  {
    try {
      $validated = $request->validate([
        'male_uuid' => 'required|string|exists:pigeons,uuid',
        'femelle_uuid' => 'required|string|exists:pigeons,uuid',
        'cage_uuid' => 'nullable|string|exists:cages,uuid',
        'date_formation' => 'nullable|date',
        'notes' => 'nullable|string',
      ]);

      // TODO: Implémenter la logique de création via un service
      return $this->error('Fonctionnalité en cours de développement', 501);
    } catch (\Exception $e) {
      return $this->error(
        'Erreur lors de la création du couple: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Modifier un couple
   * 
   * @param Request $request
   * @param string $uuid
   * @return JsonResponse
   */
  public function update(Request $request, string $uuid): JsonResponse
  {
    try {
      $couple = Couple::where('uuid', $uuid)
        ->where('user_id', auth()->id())
        ->firstOrFail();

      // TODO: Implémenter la logique de modification via un service
      return $this->error('Fonctionnalité en cours de développement', 501);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Couple non trouvé', 404);
    } catch (\Exception $e) {
      return $this->error(
        'Erreur lors de la mise à jour du couple: ' . substr($e->getMessage(), 0, 300),
        500
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

      // TODO: Implémenter la logique de suppression via un service
      return $this->error('Fonctionnalité en cours de développement', 501);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Couple non trouvé', 404);
    } catch (\Exception $e) {
      return $this->error(
        'Erreur lors de la suppression du couple: ' . substr($e->getMessage(), 0, 300),
        500
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

      // TODO: Implémenter la logique de rupture via un service
      return $this->error('Fonctionnalité en cours de développement', 501);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Couple non trouvé', 404);
    } catch (\Exception $e) {
      return $this->error(
        'Erreur lors de la rupture du couple: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }
}
