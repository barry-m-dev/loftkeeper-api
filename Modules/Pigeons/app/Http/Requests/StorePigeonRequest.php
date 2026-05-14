<?php

namespace Modules\Pigeons\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation des données lors de la création d'un pigeon
 * 
 * @package Modules\Pigeons\Http\Requests
 */
class StorePigeonRequest extends FormRequest
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
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'nom' => ['nullable', 'string', 'max:100'],
      'sexe' => ['required', 'in:MALE,FEMELLE'],
      'date_naissance' => ['nullable', 'date', 'before:today'],
      'race' => ['nullable', 'string', 'max:100'],
      'couleur' => ['nullable', 'string', 'max:100'],
      'bague_physique' => [
        'nullable',
        'string',
        'max:50',
        'unique:pigeons,bague_physique,NULL,id,user_id,' . auth()->id(),
      ],
      'pere_uuid' => [
        'nullable',
        'string',
        'exists:pigeons,uuid',
        function ($attribute, $value, $fail) {
          if ($value) {
            $pere = \Modules\Pigeons\Models\Pigeon::where('uuid', $value)->first();
            if (!$pere || $pere->user_id !== auth()->id()) {
              $fail('Le père sélectionné n\'existe pas ou ne vous appartient pas.');
            }
            if ($pere->sexe !== 'MALE') {
              $fail('Le père doit être un pigeon mâle.');
            }
          }
        },
      ],
      'mere_uuid' => [
        'nullable',
        'string',
        'exists:pigeons,uuid',
        function ($attribute, $value, $fail) {
          if ($value) {
            $mere = \Modules\Pigeons\Models\Pigeon::where('uuid', $value)->first();
            if (!$mere || $mere->user_id !== auth()->id()) {
              $fail('La mère sélectionnée n\'existe pas ou ne vous appartient pas.');
            }
            if ($mere->sexe !== 'FEMELLE') {
              $fail('La mère doit être un pigeon femelle.');
            }
          }
        },
      ],
      'cage_uuid' => [
        'nullable',
        'string',
        'exists:cages,uuid',
        function ($attribute, $value, $fail) {
          if ($value) {
            $cage = \Modules\Cages\Models\Cage::where('uuid', $value)->first();
            if (!$cage || $cage->user_id !== auth()->id()) {
              $fail('La cage sélectionnée n\'existe pas ou ne vous appartient pas.');
            }
            if ($cage->statut !== 'LIBRE') {
              $fail('La cage sélectionnée n\'est pas libre.');
            }
          }
        },
      ],
      'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // max 5MB
      'notes' => ['nullable', 'string'],
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array<string, string>
   */
  public function messages(): array
  {
    return [
      'sexe.required' => 'Le sexe du pigeon est obligatoire.',
      'sexe.in' => 'Le sexe doit être MALE ou FEMELLE.',
      'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
      'bague_physique.unique' => 'Cette bague physique est déjà utilisée pour un autre pigeon.',
      'photo.image' => 'Le fichier doit être une image.',
      'photo.mimes' => 'L\'image doit être au format JPEG, JPG, PNG ou WEBP.',
      'photo.max' => 'L\'image ne doit pas dépasser 5 MB.',
    ];
  }
}
