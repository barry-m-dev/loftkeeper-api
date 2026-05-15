<?php

namespace Modules\Couples\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Cages\Http\Resources\CageResource;
use Modules\Pigeons\Http\Resources\PigeonResource;

/**
 * Sérialisation JSON des couples (même schéma que PigeonResource pour les pigeons imbriqués : photo / photo_url).
 */
class CoupleResource extends JsonResource
{
  /**
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'uuid' => $this->uuid,
      'code' => $this->code,
      'male_id' => $this->male_id,
      'femelle_id' => $this->femelle_id,
      'cage_id' => $this->cage_id,
      'date_formation' => $this->date_formation?->format('Y-m-d'),
      'date_rupture' => $this->date_rupture?->format('Y-m-d'),
      'statut' => $this->statut,
      'notes' => $this->notes,
      'male' => new PigeonResource($this->whenLoaded('male')),
      'femelle' => new PigeonResource($this->whenLoaded('femelle')),
      'cage' => $this->when(
        $this->relationLoaded('cage'),
        fn () => $this->cage ? new CageResource($this->cage) : null
      ),
      'reproductions' => $this->whenLoaded('reproductions'),
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
    ];
  }
}
