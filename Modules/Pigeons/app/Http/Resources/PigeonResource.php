<?php

namespace Modules\Pigeons\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Cages\Http\Resources\CageResource;

/**
 * Resource pour formater les données d'un pigeon en JSON
 * 
 * @package Modules\Pigeons\Http\Resources
 */
class PigeonResource extends JsonResource
{
  /**
   * URL publique de la photo (disque `public`) ou null si aucune image enregistrée.
   * Aligné sur le front : pas d'URL « défaut » serveur ici — le placeholder est géré côté client.
   */
  protected function publicPhotoUrl(): ?string
  {
    $path = $this->photo;
    if ($path === null || $path === '') {
      return null;
    }
    if (!is_string($path)) {
      return null;
    }
    $path = trim($path);
    if ($path === '') {
      return null;
    }

    return \Storage::disk('public')->url($path);
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $photoUrl = $this->publicPhotoUrl();

    return [
      'uuid' => $this->uuid,
      'bague' => $this->bague,
      'bague_physique' => $this->bague_physique,
      'nom' => $this->nom,
      'photo' => $photoUrl,
      'photo_url' => $photoUrl,
      'sexe' => $this->sexe,
      'date_naissance' => $this->date_naissance?->format('Y-m-d'),
      'age' => $this->age,
      'race' => $this->race,
      'couleur' => $this->couleur,
      'statut' => $this->statut_disponibilite, // Statut de disponibilité (DISPONIBLE/EN_COUPLE/EN_CAGE)
      'etat' => $this->statut, // État du pigeon (ACTIF/VENDU/MORT/PERDU)
      'is_disponible' => $this->is_disponible,

      // Relations (chargées conditionnellement)
      'pere' => new PigeonResource($this->whenLoaded('pere')),
      'mere' => new PigeonResource($this->whenLoaded('mere')),
      'enfants' => PigeonResource::collection($this->whenLoaded('enfants')),
      'cage' => new CageResource($this->whenLoaded('cage')),
      'couple' => $this->when(
        $this->relationLoaded('couplesMale') || $this->relationLoaded('couplesFemelle'),
        function () {
          $couple = $this->couplesMale->first() ?? $this->couplesFemelle->first();
          return $couple ? [
            'uuid' => $couple->uuid,
            'code' => $couple->code,
            'statut' => $couple->statut,
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
          ] : null;
        }
      ),

      'notes' => $this->notes,
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
    ];
  }
}
