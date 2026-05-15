<?php

namespace Modules\Reproductions\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Couples\Models\Couple;
use Modules\Pigeons\Models\Pigeon;
use App\Services\NumberingService;
use Modules\Reproductions\Models\Reproduction;

class ReproductionService
{
  public function __construct(
    private NumberingService $numberingService
  ) {}

  /**
   * Récupérer toutes les reproductions avec filtres
   * 
   * @param array $filters
   * @return Collection
   */
  public function getAllReproductions(array $filters = []): Collection
  {
    $query = Reproduction::with(['couple.male', 'couple.femelle', 'couple.cage'])
      ->orderBy('date_ponte', 'desc');

    // Filtre par statut
    if (!empty($filters['statut'])) {
      $query->where('statut', $filters['statut']);
    }

    // Filtre par couple
    if (!empty($filters['couple_uuid'])) {
      $query->whereHas('couple', function ($q) use ($filters) {
        $q->where('uuid', $filters['couple_uuid']);
      });
    }

    // Filtre par période (date_debut et date_fin)
    if (!empty($filters['date_debut'])) {
      $query->where('date_ponte', '>=', $filters['date_debut']);
    }

    if (!empty($filters['date_fin'])) {
      $query->where('date_ponte', '<=', $filters['date_fin']);
    }

    // Recherche textuelle (code couple, bagues)
    if (!empty($filters['search'])) {
      $search = $filters['search'];
      $query->whereHas('couple', function ($q) use ($search) {
        $q->where('code', 'like', "%{$search}%")
          ->orWhereHas('male', function ($sq) use ($search) {
            $sq->where('bague', 'like', "%{$search}%");
          })
          ->orWhereHas('femelle', function ($sq) use ($search) {
            $sq->where('bague', 'like', "%{$search}%");
          });
      });
    }

    return $query->get();
  }

  /**
   * Calculer les statistiques des reproductions
   * 
   * @param Collection|null $reproductions
   * @return array
   */
  public function getStats(?Collection $reproductions = null): array
  {
    if ($reproductions === null) {
      $reproductions = Reproduction::all();
    }

    $total = $reproductions->count();
    $enCours = $reproductions->whereIn('statut', ['PONTE', 'ECLOSION', 'SEVRAGE'])->count();
    $reussies = $reproductions->where('statut', 'ENREGISTRE')->count();
    $echouees = $reproductions->where('statut', 'ECHEC')->count();
    $totalPigeonneaux = $reproductions->where('statut', 'ENREGISTRE')->sum('nb_pigeonneaux');

    $tauxReussite = $total > 0 ? round(($reussies / $total) * 100, 2) : 0;

    return [
      'total' => $total,
      'en_cours' => $enCours,
      'reussies' => $reussies,
      'echouees' => $echouees,
      'total_pigeonneaux' => $totalPigeonneaux,
      'taux_reussite' => $tauxReussite,
    ];
  }

  /**
   * Récupérer les reproductions d'un couple
   * 
   * @param int $coupleId
   * @return Collection
   */
  public function getReproductionsByCouple(int $coupleId): Collection
  {
    return Reproduction::where('couple_id', $coupleId)
      ->with(['couple.male', 'couple.femelle', 'couple.cage'])
      ->orderBy('date_ponte', 'desc')
      ->get();
  }

  /**
   * Créer une nouvelle reproduction (ponte)
   * RG-R01 à RG-R04
   * 
   * @param array $data
   * @return Reproduction
   * @throws \Exception
   */
  public function createReproduction(array $data): Reproduction
  {
    // Récupérer le couple
    $couple = Couple::where('uuid', $data['couple_uuid'])->firstOrFail();

    // RG-R01 : Vérifier que le couple est ACTIF
    if ($couple->statut !== 'ACTIF') {
      throw new \Exception("Le couple doit être actif pour enregistrer une ponte");
    }

    // RG-R02 : Vérifier le nombre d'œufs (1 ou 2)
    if ($data['nb_oeufs'] < 1 || $data['nb_oeufs'] > 2) {
      throw new \Exception("Le nombre d'œufs doit être entre 1 et 2");
    }

    // RG-R03 : Vérifier que la date de ponte n'est pas dans le futur
    $datePonte = Carbon::parse($data['date_ponte']);
    if ($datePonte->isFuture()) {
      throw new \Exception("La date de ponte ne peut pas être dans le futur");
    }

    // RG-R04 : Créer la reproduction avec statut PONTE
    $reproduction = new Reproduction();
    $reproduction->uuid = \Illuminate\Support\Str::uuid();
    $reproduction->couple_id = $couple->id;
    $reproduction->date_ponte = $datePonte;
    $reproduction->nb_oeufs = $data['nb_oeufs'];
    $reproduction->statut = 'PONTE';
    $reproduction->notes = $data['notes'] ?? null;
    $reproduction->save();

    return $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);
  }

  /**
   * Déclarer l'éclosion
   * RG-R05 à RG-R08
   * 
   * @param Reproduction $reproduction
   * @param array $data
   * @return Reproduction
   * @throws \Exception
   */
  public function declareEclosion(Reproduction $reproduction, array $data): Reproduction
  {
    // RG-R05 : Vérifier que le statut est PONTE
    if ($reproduction->statut !== 'PONTE') {
      throw new \Exception("Cette reproduction n'est pas en phase de ponte");
    }

    // RG-R06 : Vérifier le nombre de pigeonneaux
    $nbPigeonneaux = $data['nb_pigeonneaux'];
    if ($nbPigeonneaux < 0 || $nbPigeonneaux > $reproduction->nb_oeufs) {
      throw new \Exception("Le nombre de pigeonneaux doit être entre 0 et {$reproduction->nb_oeufs}");
    }

    // RG-R07 : Vérifier la date d'éclosion
    $dateEclosion = Carbon::parse($data['date_eclosion']);
    $datePonte = Carbon::parse($reproduction->date_ponte);

    if ($dateEclosion->lt($datePonte)) {
      throw new \Exception("La date d'éclosion ne peut pas être antérieure à la date de ponte");
    }

    if ($dateEclosion->isFuture()) {
      throw new \Exception("La date d'éclosion ne peut pas être dans le futur");
    }

    // Mettre à jour la reproduction
    $reproduction->date_eclosion = $dateEclosion;
    $reproduction->nb_pigeonneaux = $nbPigeonneaux;

    // RG-R08 : Changement de statut automatique
    if ($nbPigeonneaux === 0) {
      $reproduction->statut = 'ECHEC';
    } else {
      $reproduction->statut = 'ECLOSION';
    }

    if (isset($data['notes'])) {
      $reproduction->notes = $data['notes'];
    }

    $reproduction->save();

    return $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);
  }

  /**
   * Déclarer le sevrage
   * RG-R09 à RG-R11
   * 
   * @param Reproduction $reproduction
   * @param array $data
   * @return Reproduction
   * @throws \Exception
   */
  public function declareSevrage(Reproduction $reproduction, array $data): Reproduction
  {
    // RG-R09 : Vérifier que le statut est ECLOSION
    if ($reproduction->statut !== 'ECLOSION') {
      throw new \Exception("Cette reproduction n'est pas en phase d'éclosion");
    }

    // RG-R10 : Vérifier la date de sevrage
    $dateSevrage = Carbon::parse($data['date_sevrage']);
    $dateEclosion = Carbon::parse($reproduction->date_eclosion);

    if ($dateSevrage->lt($dateEclosion)) {
      throw new \Exception("La date de sevrage ne peut pas être antérieure à la date d'éclosion");
    }

    if ($dateSevrage->isFuture()) {
      throw new \Exception("La date de sevrage ne peut pas être dans le futur");
    }

    // RG-R11 : Mettre à jour avec statut SEVRAGE
    $reproduction->date_sevrage = $dateSevrage;
    $reproduction->statut = 'SEVRAGE';

    if (isset($data['notes'])) {
      $reproduction->notes = $data['notes'];
    }

    $reproduction->save();

    return $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);
  }

  /**
   * Enregistrer les pigeonneaux
   * RG-R12 à RG-R17
   * 
   * @param Reproduction $reproduction
   * @param array $pigeonneaux
   * @return Reproduction
   * @throws \Exception
   */
  public function enregistrerPigeonneaux(Reproduction $reproduction, array $pigeonneaux): Reproduction
  {
    if ($reproduction->statut === 'ENREGISTRE') {
      throw new \Exception("Les pigeonneaux de cette reproduction ont déjà été enregistrés");
    }

    // RG-R12 : Vérifier que le statut est SEVRAGE
    if ($reproduction->statut !== 'SEVRAGE') {
      throw new \Exception("Les pigeonneaux doivent être sevrés avant d'être enregistrés");
    }

    // RG-R13 : Vérifier le nombre de pigeonneaux à enregistrer
    if (count($pigeonneaux) !== $reproduction->nb_pigeonneaux) {
      throw new \Exception("Vous devez enregistrer exactement {$reproduction->nb_pigeonneaux} pigeonneau(x)");
    }

    // Récupérer le couple avec les parents
    $couple = $reproduction->couple()->with(['male', 'femelle'])->first();

    if (!$couple || !$couple->male || !$couple->femelle) {
      throw new \Exception("Le couple ou ses parents sont introuvables");
    }

    DB::beginTransaction();

    try {
      $pigeonsCreated = [];

      foreach ($pigeonneaux as $pigeonneauData) {
        // RG-R14 : Générer une bague unique
        $bague = $this->numberingService->generateBague(auth()->id());

        // RG-R15 : Parents automatiques
        // RG-R16 : Statut initial ACTIF
        $pigeon = new Pigeon();
        $pigeon->uuid = \Illuminate\Support\Str::uuid();
        $pigeon->bague = $bague;
        $pigeon->sexe = $pigeonneauData['sexe'];
        $pigeon->nom = $pigeonneauData['nom'] ?? null;
        $pigeon->couleur = $pigeonneauData['couleur'] ?? null;
        $pigeon->date_naissance = $reproduction->date_eclosion;
        $pigeon->pere_id = $couple->male->id;
        $pigeon->mere_id = $couple->femelle->id;
        $pigeon->statut = 'ACTIF';
        $pigeon->notes = $pigeonneauData['notes'] ?? null;

        // Gérer l'upload de la photo
        if (isset($pigeonneauData['photo']) && $pigeonneauData['photo'] instanceof \Illuminate\Http\UploadedFile) {
          $photo = $pigeonneauData['photo'];
          $filename = 'pigeon_' . $pigeon->uuid . '.' . $photo->getClientOriginalExtension();
          $path = $photo->storeAs('pigeons', $filename, 'public');
          $pigeon->photo = $path;
        }

        $pigeon->save();

        $pigeonsCreated[] = $pigeon;
      }

      // RG-R17 : Changement de statut reproduction
      $reproduction->statut = 'ENREGISTRE';
      $reproduction->save();

      DB::commit();

      return $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Mettre à jour une reproduction
   * RG-R18 à RG-R19
   * 
   * @param Reproduction $reproduction
   * @param array $data
   * @return Reproduction
   * @throws \Exception
   */
  public function updateReproduction(Reproduction $reproduction, array $data): Reproduction
  {
    // RG-R18 : Modification interdite si ENREGISTRE ou ECHEC
    if (in_array($reproduction->statut, ['ENREGISTRE', 'ECHEC'])) {
      // RG-R19 : Sauf pour les notes
      if (isset($data['notes']) && count($data) === 1) {
        $reproduction->notes = $data['notes'];
        $reproduction->save();
        return $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);
      }

      throw new \Exception("Impossible de modifier une reproduction terminée");
    }

    // Modification autorisée pour PONTE, ECLOSION, SEVRAGE
    if (isset($data['notes'])) {
      $reproduction->notes = $data['notes'];
    }

    // Modification de la date de ponte (seulement si PONTE)
    if (isset($data['date_ponte']) && $reproduction->statut === 'PONTE') {
      $datePonte = Carbon::parse($data['date_ponte']);
      if ($datePonte->isFuture()) {
        throw new \Exception("La date de ponte ne peut pas être dans le futur");
      }
      $reproduction->date_ponte = $datePonte;
    }

    // Modification du nombre d'œufs (seulement si PONTE)
    if (isset($data['nb_oeufs']) && $reproduction->statut === 'PONTE') {
      if ($data['nb_oeufs'] < 1 || $data['nb_oeufs'] > 2) {
        throw new \Exception("Le nombre d'œufs doit être entre 1 et 2");
      }
      $reproduction->nb_oeufs = $data['nb_oeufs'];
    }

    $reproduction->save();

    return $reproduction->load(['couple.male', 'couple.femelle', 'couple.cage']);
  }

  /**
   * Supprimer une reproduction (soft delete)
   * RG-R20 à RG-R22
   * 
   * @param Reproduction $reproduction
   * @return void
   * @throws \Exception
   */
  public function deleteReproduction(Reproduction $reproduction): void
  {
    // RG-R21 : Suppression interdite si ENREGISTRE
    if ($reproduction->statut === 'ENREGISTRE') {
      throw new \Exception("Impossible de supprimer une reproduction dont les pigeonneaux sont enregistrés");
    }

    // RG-R22 : Suppression autorisée pour PONTE, ECLOSION, SEVRAGE, ECHEC
    // RG-R20 : Soft delete
    $reproduction->delete();
  }

  /**
   * Récupérer les couples actifs pour nouvelle reproduction
   * 
   * @return Collection
   */
  public function getCouplesActifs(): Collection
  {
    return Couple::where('statut', 'ACTIF')
      ->with(['male', 'femelle', 'cage'])
      ->orderBy('code', 'asc')
      ->get();
  }

  /**
   * Calculer la date d'éclosion prévue (+21 jours)
   * 
   * @param string $datePonte
   * @return string
   */
  public function calculateDateEclosionPrevue(string $datePonte): string
  {
    return Carbon::parse($datePonte)->addDays(21)->format('Y-m-d');
  }

  /**
   * Calculer la date de sevrage prévue (+28 jours)
   * 
   * @param string $dateEclosion
   * @return string
   */
  public function calculateDateSevragePrevue(string $dateEclosion): string
  {
    return Carbon::parse($dateEclosion)->addDays(28)->format('Y-m-d');
  }
}
