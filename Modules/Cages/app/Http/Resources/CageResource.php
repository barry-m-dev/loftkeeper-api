<?php

namespace Modules\Cages\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour transformer les données d'une cage
 */
class CageResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'uuid' => $this->uuid,
      'numero' => $this->numero,
      'nom' => $this->nom,
      'superficie' => $this->superficie,
      'statut' => $this->statut,
      'notes' => $this->notes,
      'occupant' => $this->getOccupantData(),
      'created_at' => $this->created_at?->toISOString(),
      'updated_at' => $this->updated_at?->toISOString(),
    ];
  }

  /**
   * Récupère les données de l'occupant (pigeon ou couple)
   *
   * @return array|null
   */
  private function getOccupantData(): ?array
  {
    if ($this->statut === 'LIBRE') {
      return null;
    }

    if ($this->statut === 'OCCUPE_PIGEON' && $this->pigeon) {
      $p = $this->pigeon;
      return [
        'type' => 'pigeon',
        'uuid' => $p->uuid,
        'label' => $p->bague ?? 'Pigeon',
        'detail' => $p->nom ?? null,
        'photo_url' => $p->photo
          ? \Storage::disk('public')->url($p->photo)
          : null,
        'bague' => $p->bague,
        'nom' => $p->nom,
        'sexe' => $p->sexe,
        'race' => $p->race,
        'couleur' => $p->couleur,
        'bague_physique' => $p->bague_physique,
        'age' => $p->age,
        'statut' => $p->statut_disponibilite,
      ];
    }

    if ($this->statut === 'OCCUPE_COUPLE' && $this->couple) {
      $maleLabel = $this->couple->male?->bague ?? 'Mâle';
      $femelleLabel = $this->couple->femelle?->bague ?? 'Femelle';

      return [
        'type' => 'couple',
        'uuid' => $this->couple->uuid,
        'label' => $this->couple->code ?? 'Couple',
        'detail' => "{$maleLabel} × {$femelleLabel}",
        'photo_url' => null,
      ];
    }

    return null;
  }
}
