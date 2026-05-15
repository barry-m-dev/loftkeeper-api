<?php

namespace Modules\Reproductions\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Couples\Http\Resources\CoupleResource;
use Modules\Pigeons\Http\Resources\PigeonResource;

class ReproductionResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'uuid' => $this->uuid,
      'couple_id' => $this->couple_id,
      'date_ponte' => $this->date_ponte ? Carbon::parse($this->date_ponte)->format('Y-m-d') : null,
      'nb_oeufs' => $this->nb_oeufs,
      'date_eclosion' => $this->date_eclosion ? Carbon::parse($this->date_eclosion)->format('Y-m-d') : null,
      'nb_pigeonneaux' => $this->nb_pigeonneaux,
      'date_sevrage' => $this->date_sevrage ? Carbon::parse($this->date_sevrage)->format('Y-m-d') : null,
      'statut' => $this->statut,
      'notes' => $this->notes,

      // Dates prévues calculées
      'date_eclosion_prevue' => $this->date_ponte
        ? Carbon::parse($this->date_ponte)->addDays(21)->format('Y-m-d')
        : null,
      'date_sevrage_prevue' => $this->date_eclosion
        ? Carbon::parse($this->date_eclosion)->addDays(28)->format('Y-m-d')
        : null,

      // Relations
      'couple' => new CoupleResource($this->whenLoaded('couple')),
      'pigeonneaux' => PigeonResource::collection($this->whenLoaded('pigeonneaux')),

      // Timestamps
      'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
      'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
    ];
  }
}
