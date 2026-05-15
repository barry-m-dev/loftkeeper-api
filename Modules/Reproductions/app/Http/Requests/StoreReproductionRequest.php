<?php

namespace Modules\Reproductions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReproductionRequest extends FormRequest
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
    return [
      'couple_uuid' => 'required|string|exists:couples,uuid',
      'date_ponte' => 'required|date|before_or_equal:today',
      'nb_oeufs' => 'required|integer|min:1|max:2',
      'notes' => 'nullable|string|max:1000',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'couple_uuid.required' => 'Le couple est obligatoire',
      'couple_uuid.exists' => 'Le couple sélectionné n\'existe pas',
      'date_ponte.required' => 'La date de ponte est obligatoire',
      'date_ponte.date' => 'La date de ponte doit être une date valide',
      'date_ponte.before_or_equal' => 'La date de ponte ne peut pas être dans le futur',
      'nb_oeufs.required' => 'Le nombre d\'œufs est obligatoire',
      'nb_oeufs.integer' => 'Le nombre d\'œufs doit être un nombre entier',
      'nb_oeufs.min' => 'Le nombre d\'œufs doit être au minimum 1',
      'nb_oeufs.max' => 'Le nombre d\'œufs ne peut pas dépasser 2',
      'notes.max' => 'Les notes ne peuvent pas dépasser 1000 caractères',
    ];
  }
}
