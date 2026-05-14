<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
  /**
   * Récupérer tous les KPIs du dashboard pour un utilisateur
   *
   * @param int $userId
   * @return array
   */
  public function getKpis(int $userId): array
  {
    return [
      'cages' => $this->getCagesKpis($userId),
      'pigeons' => $this->getPigeonsKpis($userId),
      'couples' => $this->getCouplesKpis($userId),
      'ventes' => $this->getVentesKpis($userId),
      'totaux' => $this->getTotauxKpis($userId),
      'pigeons_ajoutes_semaine' => $this->getPigeonsAjoutesSemaine($userId),
    ];
  }

  /**
   * KPIs des cages (libres, occupées pigeon, occupées couple)
   *
   * @param int $userId
   * @return array
   */
  private function getCagesKpis(int $userId): array
  {
    $cages = DB::table('cages')
      ->where('user_id', $userId)
      ->whereNull('deleted_at')
      ->select('statut', DB::raw('COUNT(*) as count'))
      ->groupBy('statut')
      ->get()
      ->keyBy('statut');

    return [
      'libres' => $cages->has('LIBRE') ? (int) $cages->get('LIBRE')->count : 0,
      'occupees_pigeon' => $cages->has('OCCUPE_PIGEON') ? (int) $cages->get('OCCUPE_PIGEON')->count : 0,
      'occupees_couple' => $cages->has('OCCUPE_COUPLE') ? (int) $cages->get('OCCUPE_COUPLE')->count : 0,
      'total' => (int) $cages->sum('count'),
    ];
  }

  /**
   * KPIs des pigeons (actifs, perdus)
   *
   * @param int $userId
   * @return array
   */
  private function getPigeonsKpis(int $userId): array
  {
    $pigeons = DB::table('pigeons')
      ->where('user_id', $userId)
      ->whereNull('deleted_at')
      ->select('statut', DB::raw('COUNT(*) as count'))
      ->whereIn('statut', ['ACTIF', 'PERDU'])
      ->groupBy('statut')
      ->get()
      ->keyBy('statut');

    return [
      'actifs' => $pigeons->has('ACTIF') ? (int) $pigeons->get('ACTIF')->count : 0,
      'perdus' => $pigeons->has('PERDU') ? (int) $pigeons->get('PERDU')->count : 0,
      'total' => (int) $pigeons->sum('count'),
    ];
  }

  /**
   * KPIs des couples et reproductions (ponte, éclosion, sevrage)
   *
   * @param int $userId
   * @return array
   */
  private function getCouplesKpis(int $userId): array
  {
    // Nombre de couples actifs
    $couplesActifs = DB::table('couples')
      ->where('user_id', $userId)
      ->where('statut', 'ACTIF')
      ->whereNull('deleted_at')
      ->count();

    // Reproductions par statut (uniquement pour couples actifs)
    $reproductions = DB::table('reproductions')
      ->join('couples', 'reproductions.couple_id', '=', 'couples.id')
      ->where('couples.user_id', $userId)
      ->where('couples.statut', 'ACTIF')
      ->whereNull('couples.deleted_at')
      ->whereNull('reproductions.deleted_at')
      ->select('reproductions.statut', DB::raw('COUNT(DISTINCT reproductions.id) as count'))
      ->whereIn('reproductions.statut', ['PONTE', 'ECLOSION', 'SEVRAGE'])
      ->groupBy('reproductions.statut')
      ->get()
      ->keyBy('statut');

    return [
      'en_ponte' => $reproductions->has('PONTE') ? (int) $reproductions->get('PONTE')->count : 0,
      'en_eclosion' => $reproductions->has('ECLOSION') ? (int) $reproductions->get('ECLOSION')->count : 0,
      'en_sevrage' => $reproductions->has('SEVRAGE') ? (int) $reproductions->get('SEVRAGE')->count : 0,
      'actifs' => (int) $couplesActifs,
    ];
  }

  /**
   * KPIs des ventes (nombre + total prix)
   *
   * @param int $userId
   * @return array
   */
  private function getVentesKpis(int $userId): array
  {
    $ventes = DB::table('sorties')
      ->join('pigeons', 'sorties.pigeon_id', '=', 'pigeons.id')
      ->where('pigeons.user_id', $userId)
      ->where('sorties.type', 'VENTE')
      ->whereNull('sorties.deleted_at')
      ->whereNull('pigeons.deleted_at')
      ->select(
        DB::raw('COUNT(*) as nombre'),
        DB::raw('COALESCE(SUM(sorties.prix), 0) as total_prix')
      )
      ->first();

    return [
      'nombre' => $ventes->nombre ?? 0,
      'total_prix' => (float) ($ventes->total_prix ?? 0),
      'devise' => 'FCFA',
    ];
  }

  /**
   * KPIs totaux (pigeons total, œufs total, cages total)
   *
   * @param int $userId
   * @return array
   */
  private function getTotauxKpis(int $userId): array
  {
    // Total pigeons (tous statuts)
    $totalPigeons = DB::table('pigeons')
      ->where('user_id', $userId)
      ->whereNull('deleted_at')
      ->count();

    // Total œufs (somme des nb_oeufs dans reproductions)
    $totalOeufs = DB::table('reproductions')
      ->join('couples', 'reproductions.couple_id', '=', 'couples.id')
      ->where('couples.user_id', $userId)
      ->whereNull('couples.deleted_at')
      ->whereNull('reproductions.deleted_at')
      ->sum('reproductions.nb_oeufs');

    // Total cages
    $totalCages = DB::table('cages')
      ->where('user_id', $userId)
      ->whereNull('deleted_at')
      ->count();

    return [
      'total_pigeons' => $totalPigeons,
      'total_oeufs' => (int) $totalOeufs,
      'total_cages' => $totalCages,
    ];
  }

  /**
   * Pigeons ajoutés dans les 7 derniers jours (par jour)
   *
   * @param int $userId
   * @return array
   */
  private function getPigeonsAjoutesSemaine(int $userId): array
  {
    // Récupérer les pigeons ajoutés dans les 7 derniers jours
    $pigeonsParJour = DB::table('pigeons')
      ->where('user_id', $userId)
      ->whereNull('deleted_at')
      ->where('created_at', '>=', Carbon::now()->subDays(7)->startOfDay())
      ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('COUNT(*) as count')
      )
      ->groupBy('date')
      ->orderBy('date')
      ->get()
      ->keyBy('date');

    // Générer les 7 derniers jours avec count = 0 si aucun pigeon ajouté
    $result = [];
    $joursEnFrancais = [
      'Monday' => 'Lundi',
      'Tuesday' => 'Mardi',
      'Wednesday' => 'Mercredi',
      'Thursday' => 'Jeudi',
      'Friday' => 'Vendredi',
      'Saturday' => 'Samedi',
      'Sunday' => 'Dimanche',
    ];

    for ($i = 6; $i >= 0; $i--) {
      $date = Carbon::now()->subDays($i);
      $dateStr = $date->format('Y-m-d');
      $jourAnglais = $date->format('l');

      $result[] = [
        'date' => $dateStr,
        'jour' => $joursEnFrancais[$jourAnglais] ?? $jourAnglais,
        'count' => $pigeonsParJour->has($dateStr) ? (int) $pigeonsParJour->get($dateStr)->count : 0,
      ];
    }

    return $result;
  }
}
