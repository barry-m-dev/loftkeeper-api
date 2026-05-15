<?php

namespace Modules\Pigeons\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation des données lors de la modification d'un pigeon
 *
 * @package Modules\Pigeons\Http\Requests
 */
class UpdatePigeonRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Chaînes vides → null pour que « omission / champ vidé » ne fasse pas échouer `exists`.
   */
  protected function prepareForValidation(): void
  {
    if ($this->has('pere_uuid') && $this->input('pere_uuid') === '') {
      $this->merge(['pere_uuid' => null]);
    }
    if ($this->has('mere_uuid') && $this->input('mere_uuid') === '') {
      $this->merge(['mere_uuid' => null]);
    }
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    $pigeonId = $this->route('uuid')
      ? \Modules\Pigeons\Models\Pigeon::where('uuid', $this->route('uuid'))->value('id')
      : null;

    $subjectUuid = (string) $this->route('uuid', '');

    return [
      'nom' => ['nullable', 'string', 'max:100'],
      'sexe' => ['sometimes', 'required', 'in:MALE,FEMELLE'],
      'date_naissance' => ['nullable', 'date', 'before:today'],
      'race' => ['nullable', 'string', 'max:100'],
      'couleur' => ['nullable', 'string', 'max:100'],
      'bague_physique' => [
        'nullable',
        'string',
        'max:50',
        'unique:pigeons,bague_physique,' . $pigeonId . ',id,user_id,' . auth()->id(),
      ],
      'pere_uuid' => [
        'nullable',
        'string',
        'exists:pigeons,uuid',
        function ($attribute, $value, $fail) use ($subjectUuid) {
          if (!$value) {
            return;
          }
          if ($value === $subjectUuid) {
            $fail('Un pigeon ne peut pas être son propre père.');
            return;
          }
          $pere = \Modules\Pigeons\Models\Pigeon::where('uuid', $value)->first();
          if (!$pere || $pere->user_id !== auth()->id()) {
            $fail('Le père sélectionné n\'existe pas ou ne vous appartient pas.');
            return;
          }
          if ($pere->sexe !== 'MALE') {
            $fail('Le père doit être un pigeon mâle.');
          }
        },
      ],
      'mere_uuid' => [
        'nullable',
        'string',
        'exists:pigeons,uuid',
        function ($attribute, $value, $fail) use ($subjectUuid) {
          if (!$value) {
            return;
          }
          if ($value === $subjectUuid) {
            $fail('Un pigeon ne peut pas être sa propre mère.');
            return;
          }
          $mere = \Modules\Pigeons\Models\Pigeon::where('uuid', $value)->first();
          if (!$mere || $mere->user_id !== auth()->id()) {
            $fail('La mère sélectionnée n\'existe pas ou ne vous appartient pas.');
            return;
          }
          if ($mere->sexe !== 'FEMELLE') {
            $fail('La mère doit être un pigeon femelle.');
          }
        },
      ],
      'cage_uuid' => [
        'nullable',
        'string',
        'exists:cages,uuid',
        function ($attribute, $value, $fail) use ($pigeonId) {
          if ($value) {
            $cage = \Modules\Cages\Models\Cage::where('uuid', $value)->first();
            if (!$cage || $cage->user_id !== auth()->id()) {
              $fail('La cage sélectionnée n\'existe pas ou ne vous appartient pas.');
            }
            // Vérifier si la cage est libre OU occupée par ce pigeon
            if ($cage->statut !== 'LIBRE' && $cage->pigeon?->id !== $pigeonId) {
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
      'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
      'bague_physique.unique' => 'Cette bague physique est déjà utilisée pour un autre pigeon.',
      'photo.image' => 'Le fichier doit être une image.',
      'photo.mimes' => 'L\'image doit être au format JPEG, JPG, PNG ou WEBP.',
      'photo.max' => 'L\'image ne doit pas dépasser 5 MB.',
    ];
  }
}
