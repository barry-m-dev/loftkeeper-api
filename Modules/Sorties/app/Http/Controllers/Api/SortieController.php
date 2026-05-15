<?php

namespace Modules\Sorties\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pigeons\Models\Pigeon;
use Modules\Sorties\Http\Requests\StoreSortieRequest;
use Modules\Sorties\Models\Sortie;
use Modules\Sorties\Services\SortieService;

class SortieController extends Controller
{
    protected SortieService $sortieService;

    public function __construct(SortieService $sortieService)
    {
        $this->sortieService = $sortieService;
    }

    /**
     * Liste des sorties
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sortie::with(['pigeon'])
            ->whereHas('pigeon', function ($q) {
                $q->where('user_id', auth()->id());
            });

        // Filtrer par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Recherche par bague pigeon
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('pigeon', function ($q) use ($search) {
                $q->where('bague', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%");
            });
        }

        // Période
        if ($request->filled('date_debut')) {
            $query->whereDate('date_sortie', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_sortie', '<=', $request->date_fin);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $sorties = $query->orderBy('date_sortie', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage);

        // Calculate Stats for all user sorties (unfiltered for total, or filtered based on the query? Usually stats are overall)
        $userId = auth()->id();
        $totalSorties = Sortie::whereHas('pigeon', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();

        $ventes = Sortie::whereHas('pigeon', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('type', 'VENTE')->count();

        $deces = Sortie::whereHas('pigeon', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('type', 'DECES')->count();

        $pertes = Sortie::whereHas('pigeon', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('type', 'PERTE')->count();

        return response()->json([
            'success' => true,
            'data' => $sorties->items(),
            'meta' => [
                'current_page' => $sorties->currentPage(),
                'last_page' => $sorties->lastPage(),
                'per_page' => $sorties->perPage(),
                'total' => $sorties->total(),
                'stats' => [
                    'total' => $totalSorties,
                    'ventes' => $ventes,
                    'deces' => $deces,
                    'pertes' => $pertes,
                ]
            ]
        ]);
    }

    /**
     * Déclarer une sortie pour un pigeon
     */
    public function store(StoreSortieRequest $request, string $pigeonUuid): JsonResponse
    {
        try {
            $pigeon = Pigeon::where('uuid', $pigeonUuid)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $sortie = $this->sortieService->declarerSortie($pigeon, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Sortie déclarée avec succès',
                'data' => $sortie,
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pigeon introuvable',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
