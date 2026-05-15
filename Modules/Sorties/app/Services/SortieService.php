<?php

namespace Modules\Sorties\Services;

use Illuminate\Support\Facades\DB;
use Modules\Pigeons\Models\Pigeon;
use Modules\Sorties\Models\Sortie;
use Modules\Couples\Models\Couple;
use Modules\Couples\Services\CoupleService;
use Modules\Cages\Models\Cage;

class SortieService
{
    protected CoupleService $coupleService;

    public function __construct(CoupleService $coupleService)
    {
        $this->coupleService = $coupleService;
    }

    /**
     * Déclarer une sortie pour un pigeon
     * 
     * @param Pigeon $pigeon
     * @param array $data (type, date_sortie, prix, acheteur, cause, circonstance, notes)
     * @return Sortie
     */
    public function declarerSortie(Pigeon $pigeon, array $data): Sortie
    {
        // Un pigeon déjà sorti ne peut pas sortir à nouveau
        if (in_array($pigeon->statut, ['VENDU', 'MORT', 'PERDU'])) {
            throw new \Exception("Ce pigeon est déjà enregistré comme sorti ({$pigeon->statut})");
        }

        return DB::transaction(function () use ($pigeon, $data) {
            // 1. Libérer la cage si le pigeon y était assigné individuellement
            if ($pigeon->cage_id) {
                Cage::where('id', $pigeon->cage_id)->update(['statut' => 'LIBRE']);
                $pigeon->cage_id = null;
            }

            // 2. Si le pigeon fait partie d'un couple actif, on rompt le couple
            $coupleActif = Couple::where('statut', 'ACTIF')
                ->where(function ($q) use ($pigeon) {
                    $q->where('male_id', $pigeon->id)
                      ->orWhere('femelle_id', $pigeon->id);
                })->first();

            if ($coupleActif) {
                // Utiliser le CoupleService pour appliquer les règles de rupture proprement
                $this->coupleService->rompre($coupleActif);
                // Le CoupleService gère déjà la libération de la cage du couple !
            }

            // 3. Créer l'enregistrement de la sortie
            $sortie = Sortie::create([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'pigeon_id' => $pigeon->id,
                'type' => $data['type'],
                'date_sortie' => $data['date_sortie'],
                'prix' => $data['type'] === 'VENTE' ? ($data['prix'] ?? null) : null,
                'acheteur' => $data['type'] === 'VENTE' ? ($data['acheteur'] ?? null) : null,
                'cause' => $data['type'] === 'DECES' ? ($data['cause'] ?? null) : null,
                'circonstance' => $data['type'] === 'PERTE' ? ($data['circonstance'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
            ]);

            // 4. Mettre à jour le statut du pigeon
            $pigeon->statut = match ($data['type']) {
                'VENTE' => 'VENDU',
                'DECES' => 'MORT',
                'PERTE' => 'PERDU',
                default => 'ACTIF', // Fallback improbable vu la validation
            };
            $pigeon->save();

            return $sortie->load('pigeon');
        });
    }
}
