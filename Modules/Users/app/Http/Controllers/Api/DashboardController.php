<?php

namespace Modules\Users\Http\Controllers\Api;

use App\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Users\Services\DashboardService;

class DashboardController extends Controller
{
  use ApiResponse;

  protected DashboardService $dashboardService;

  public function __construct(DashboardService $dashboardService)
  {
    $this->dashboardService = $dashboardService;
  }

  /**
   * Récupérer les KPIs du dashboard
   *
   * @return JsonResponse
   */
  public function kpis(): JsonResponse
  {
    try {
      $userId = auth()->id();

      $kpis = $this->dashboardService->getKpis($userId);

      return $this->success(
        $kpis,
        'KPIs récupérés avec succès'
      );
    } catch (\Exception $e) {
      return $this->error(
        'Erreur lors de la récupération des KPIs',
        500,
        ['error' => $e->getMessage()]
      );
    }
  }
}
