<?php

namespace Modules\Reproductions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReproductionRequest extends FormRequest
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
      'date_ponte' => 'sometimes|date|before_or_equal:today',
      'nb_oeufs' => 'sometimes|integer|min:1|max:2',
      'notes' => 'nullable|string|max:1000',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'date_ponte.date' => 'La date de ponte doit être une date valide',
      'date_ponte.before_or_equal' => 'La date de ponte ne peut pas être dans le futur',
      'nb_oeufs.integer' => 'Le nombre d\'œufs doit être un nombre entier',
      'nb_oeufs.min' => 'Le nombre d\'œufs doit être au minimum 1',
      'nb_oeufs.max' => 'Le nombre d\'œufs ne peut pas dépasser 2',
      'notes.max' => 'Les notes ne peuvent pas dépasser 1000 caractères',
    ];
  }
}
