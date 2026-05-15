<?php

namespace Modules\Reproductions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnregistrerPigeonneauxRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    $uuid = $this->route('uuid');
    $reproduction = \Modules\Reproductions\Models\Reproduction::where('uuid', $uuid)->first();
    $nbPigeonneaux = $reproduction ? $reproduction->nb_pigeonneaux : 2;

    return [
      'pigeonneaux' => "required|array|size:{$nbPigeonneaux}",
      'pigeonneaux.*.sexe' => 'required|in:MALE,FEMELLE',
      'pigeonneaux.*.couleur' => 'nullable|string|max:50',
      'pigeonneaux.*.nom' => 'nullable|string|max:100',
      'pigeonneaux.*.notes' => 'nullable|string|max:500',
      'pigeonneaux.*.photo' => 'nullable|image|max:5120',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'pigeonneaux.required' => 'Les informations des pigeonneaux sont obligatoires',
      'pigeonneaux.array' => 'Les pigeonneaux doivent être un tableau',
      'pigeonneaux.size' => 'Vous devez enregistrer exactement :size pigeonneau(x)',
      'pigeonneaux.*.sexe.required' => 'Le sexe du pigeonneau est obligatoire',
      'pigeonneaux.*.sexe.in' => 'Le sexe doit être MALE ou FEMELLE',
      'pigeonneaux.*.couleur.max' => 'La couleur ne peut pas dépasser 50 caractères',
      'pigeonneaux.*.nom.max' => 'Le nom ne peut pas dépasser 100 caractères',
      'pigeonneaux.*.notes.max' => 'Les notes ne peuvent pas dépasser 500 caractères',
      'pigeonneaux.*.photo.image' => 'Le fichier doit être une image valide',
      'pigeonneaux.*.photo.max' => 'La photo ne doit pas dépasser 5Mo',
    ];
  }
}
