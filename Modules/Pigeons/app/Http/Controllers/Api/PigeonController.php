<?php

namespace Modules\Pigeons\Http\Controllers\Api;

use App\Core\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Pigeons\Http\Requests\StorePigeonRequest;
use Modules\Pigeons\Http\Requests\UpdatePigeonRequest;
use Modules\Pigeons\Http\Resources\PigeonResource;
use Modules\Pigeons\Models\Pigeon;
use Modules\Pigeons\Services\PigeonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller pour la gestion des pigeons
 * Orchestrateur - Délègue la logique métier au PigeonService
 * 
 * @package Modules\Pigeons\Http\Controllers\Api
 */
class PigeonController extends Controller
{
  use ApiResponse, AuthorizesRequests;

  protected PigeonService $pigeonService;

  /**
   * Constructeur
   * 
   * @param PigeonService $pigeonService
   */
  public function __construct(PigeonService $pigeonService)
  {
    $this->pigeonService = $pigeonService;
  }

  /**
   * Liste des pigeons avec filtres
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function index(Request $request): JsonResponse
  {
    try {
      $filters = $request->only(['statut', 'sexe', 'race', 'search']);
      $pigeons = $this->pigeonService->getAllPigeons($filters);
      $stats = $this->pigeonService->getStats($pigeons, auth()->id());

      \Log::info('Pigeons index', [
        'filters' => $filters,
        'pigeons_count' => $pigeons->count(),
        'stats' => $stats,
        'first_pigeon_statut' => $pigeons->first()?->statut ?? 'none',
      ]);

      return $this->success([
        'data' => PigeonResource::collection($pigeons)->resolve(),
        'meta' => $stats,
      ]);
    } catch (\Exception $e) {
      \Log::error('Erreur index pigeons', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return $this->error(
        'Erreur lors de la récupération des pigeons: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Créer un pigeon
   * 
   * @param StorePigeonRequest $request
   * @return JsonResponse
   */
  public function store(StorePigeonRequest $request): JsonResponse
  {
    try {
      $pigeon = $this->pigeonService->createPigeon(
        $request->validated(),
        auth()->id()
      );

      return $this->success(
        new PigeonResource($pigeon->load(['pere', 'mere', 'cage'])),
        'Pigeon créé avec succès',
        201
      );
    } catch (\Exception $e) {
      \Log::error('Erreur création pigeon', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return $this->error(
        'Erreur lors de la création du pigeon: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Détail d'un pigeon
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function show(string $uuid): JsonResponse
  {
    try {
      $pigeon = Pigeon::where('uuid', $uuid)
        ->with([
          'pere',
          'mere',
          'enfants',
          'cage',
          'couplesMale.femelle',
          'couplesFemelle.male',
          'sortie'
        ])
        ->firstOrFail();

      return $this->success(new PigeonResource($pigeon));
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Pigeon non trouvé', 404);
    } catch (\Exception $e) {
      \Log::error('Erreur show pigeon', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération du pigeon: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Modifier un pigeon
   * 
   * @param UpdatePigeonRequest $request
   * @param string $uuid
   * @return JsonResponse
   */
  public function update(UpdatePigeonRequest $request, string $uuid): JsonResponse
  {
    try {
      $pigeon = Pigeon::where('uuid', $uuid)->firstOrFail();

      $updatedPigeon = $this->pigeonService->updatePigeon(
        $pigeon,
        $request->validated()
      );

      return $this->success(
        new PigeonResource($updatedPigeon),
        'Pigeon mis à jour avec succès'
      );
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Pigeon non trouvé', 404);
    } catch (\Exception $e) {
      \Log::error('Erreur update pigeon', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la mise à jour du pigeon: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Supprimer un pigeon
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function destroy(string $uuid): JsonResponse
  {
    try {
      $pigeon = Pigeon::where('uuid', $uuid)->firstOrFail();

      $this->pigeonService->deletePigeon($pigeon);

      return $this->success(null, 'Pigeon supprimé avec succès');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Pigeon non trouvé', 404);
    } catch (\Exception $e) {
      \Log::error('Erreur delete pigeon', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        substr($e->getMessage(), 0, 300),
        422
      );
    }
  }

  /**
   * Généalogie d'un pigeon
   * 
   * @param string $uuid
   * @return JsonResponse
   */
  public function genealogy(string $uuid): JsonResponse
  {
    try {
      $pigeon = Pigeon::where('uuid', $uuid)->firstOrFail();
      $genealogy = $this->pigeonService->getPigeonGenealogy($pigeon);

      return $this->success($genealogy);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->error('Pigeon non trouvé', 404);
    } catch (\Exception $e) {
      \Log::error('Erreur genealogy pigeon', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération de la généalogie: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Pigeons disponibles (tous ou filtrés par sexe)
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function disponibles(Request $request): JsonResponse
  {
    try {
      // Validation optionnelle du sexe
      $validated = $request->validate([
        'sexe' => 'nullable|in:MALE,FEMELLE',
      ]);

      $sexe = $validated['sexe'] ?? null;

      // Si sexe fourni, filtrer par sexe
      if ($sexe) {
        $pigeons = $this->pigeonService->getPigeonsDisponiblesPourCouple($sexe);
      } else {
        // Retourner tous les pigeons disponibles (mâles et femelles)
        $males = $this->pigeonService->getPigeonsDisponiblesPourCouple('MALE');
        $femelles = $this->pigeonService->getPigeonsDisponiblesPourCouple('FEMELLE');
        $pigeons = $males->merge($femelles);
      }

      return $this->success([
        'data' => PigeonResource::collection($pigeons)->resolve()
      ]);
    } catch (\Exception $e) {
      \Log::error('Erreur disponibles pigeons', [
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération des pigeons disponibles: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Contexte sélection parents à l’édition : parents actuels + listes de candidats
   * (tous états, comme for-parents) hors le pigeon lui-même.
   */
  public function forParentEdit(string $uuid): JsonResponse
  {
    try {
      $pigeon = Pigeon::where('uuid', $uuid)->firstOrFail();
      $ctx = $this->pigeonService->getParentEditContext($pigeon);

      $serialize = static function ($model) {
        return $model ? (new PigeonResource($model))->resolve() : null;
      };

      return $this->success([
        'pere' => $serialize($ctx['pere'] ?? null),
        'mere' => $serialize($ctx['mere'] ?? null),
        'candidats_males' => PigeonResource::collection($ctx['candidats_males'])->resolve(),
        'candidats_femelles' => PigeonResource::collection($ctx['candidats_femelles'])->resolve(),
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->notFound('Pigeon non trouvé.');
    } catch (\Exception $e) {
      \Log::error('Erreur forParentEdit pigeon', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération du contexte parents: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Tous les pigeons pour sélection de parents (incluant morts/vendus/perdus)
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function forParents(Request $request): JsonResponse
  {
    try {
      $validated = $request->validate([
        'sexe' => 'nullable|in:MALE,FEMELLE',
      ]);

      $query = Pigeon::query();

      // Filtrer par sexe si fourni
      if (isset($validated['sexe'])) {
        $query->where('sexe', $validated['sexe']);
      }

      $pigeons = $query->orderBy('bague', 'desc')->get();

      return $this->success([
        'data' => PigeonResource::collection($pigeons)->resolve()
      ]);
    } catch (\Exception $e) {
      \Log::error('Erreur forParents pigeons', [
        'message' => $e->getMessage(),
      ]);
      return $this->error(
        'Erreur lors de la récupération des pigeons: ' . substr($e->getMessage(), 0, 300),
        500
      );
    }
  }

  /**
   * Historique complet d'un pigeon (timeline)
   * Retourne : couples, reproductions, sortie, dates clés
   *
   * @param string $uuid
   * @return JsonResponse
   */
  public function historique(string $uuid): JsonResponse
  {
    try {
      $pigeon = Pigeon::where('uuid', $uuid)
        ->where('user_id', auth()->id())
        ->firstOrFail();

      // ── Couples (tous, pas seulement actifs) ──
      $couples = \Modules\Couples\Models\Couple::with(['male:id,uuid,bague,nom', 'femelle:id,uuid,bague,nom', 'cage:id,uuid,numero,nom'])
        ->where(function ($q) use ($pigeon) {
          $q->where('male_id', $pigeon->id)
            ->orWhere('femelle_id', $pigeon->id);
        })
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($couple) {
          return [
            'uuid' => $couple->uuid,
            'code' => $couple->code,
            'statut' => $couple->statut,
            'date_formation' => $couple->date_formation,
            'date_rupture' => $couple->date_rupture,
            'created_at' => $couple->created_at,
            'male' => $couple->male ? [
              'uuid' => $couple->male->uuid,
              'bague' => $couple->male->bague,
              'nom' => $couple->male->nom,
            ] : null,
            'femelle' => $couple->femelle ? [
              'uuid' => $couple->femelle->uuid,
              'bague' => $couple->femelle->bague,
              'nom' => $couple->femelle->nom,
            ] : null,
            'cage' => $couple->cage ? [
              'uuid' => $couple->cage->uuid,
              'numero' => $couple->cage->numero,
              'nom' => $couple->cage->nom,
            ] : null,
          ];
        });

      // ── Reproductions (via couples de ce pigeon) ──
      $coupleIds = \Modules\Couples\Models\Couple::where(function ($q) use ($pigeon) {
        $q->where('male_id', $pigeon->id)
          ->orWhere('femelle_id', $pigeon->id);
      })->pluck('id');

      $reproductions = \Modules\Reproductions\Models\Reproduction::with(['couple:id,uuid,code'])
        ->whereIn('couple_id', $coupleIds)
        ->orderBy('date_ponte', 'desc')
        ->get()
        ->map(function ($r) {
          return [
            'uuid' => $r->uuid,
            'couple_code' => $r->couple?->code,
            'statut' => $r->statut,
            'nb_oeufs' => $r->nb_oeufs,
            'nb_pigeonneaux' => $r->nb_pigeonneaux,
            'date_ponte' => $r->date_ponte,
            'date_eclosion' => $r->date_eclosion,
            'date_sevrage' => $r->date_sevrage,
            'created_at' => $r->created_at,
          ];
        });

      // ── Enfants (pigeonneaux) ──
      $enfants = Pigeon::withoutGlobalScopes()
        ->where(function ($q) use ($pigeon) {
          $q->where('pere_id', $pigeon->id)
            ->orWhere('mere_id', $pigeon->id);
        })
        ->select('uuid', 'bague', 'nom', 'sexe', 'date_naissance', 'statut', 'created_at')
        ->orderBy('date_naissance', 'desc')
        ->get();

      // ── Sortie ──
      $sortie = $pigeon->sortie;
      $sortieData = $sortie ? [
        'uuid' => $sortie->uuid,
        'type' => $sortie->type,
        'date_sortie' => $sortie->date_sortie,
        'prix' => $sortie->prix,
        'acheteur' => $sortie->acheteur,
        'cause' => $sortie->cause,
        'circonstance' => $sortie->circonstance,
        'notes' => $sortie->notes,
      ] : null;

      return $this->success([
        'pigeon' => [
          'uuid' => $pigeon->uuid,
          'bague' => $pigeon->bague,
          'nom' => $pigeon->nom,
          'sexe' => $pigeon->sexe,
          'date_naissance' => $pigeon->date_naissance,
          'created_at' => $pigeon->created_at,
        ],
        'couples' => $couples,
        'reproductions' => $reproductions,
        'enfants' => $enfants,
        'sortie' => $sortieData,
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return $this->notFound('Pigeon non trouvé.');
    } catch (\Exception $e) {
      \Log::error('Erreur historique pigeon', [
        'uuid' => $uuid,
        'message' => $e->getMessage(),
      ]);
      return $this->error('Erreur lors de la récupération de l\'historique: ' . substr($e->getMessage(), 0, 300), 500);
    }
  }
}
