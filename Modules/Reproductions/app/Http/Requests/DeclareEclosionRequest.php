<?php

namespace Modules\Reproductions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeclareEclosionRequest extends FormRequest
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
    $reproduction = $this->route('reproduction');
    $maxPigeonneaux = $reproduction ? $reproduction->nb_oeufs : 2;

    return [
      'date_eclosion' => 'required|date|before_or_equal:today',
      'nb_pigeonneaux' => "required|integer|min:0|max:{$maxPigeonneaux}",
      'notes' => 'nullable|string|max:1000',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'date_eclosion.required' => 'La date d\'éclosion est obligatoire',
      'date_eclosion.date' => 'La date d\'éclosion doit être une date valide',
      'date_eclosion.before_or_equal' => 'La date d\'éclosion ne peut pas être dans le futur',
      'nb_pigeonneaux.required' => 'Le nombre de pigeonneaux est obligatoire',
      'nb_pigeonneaux.integer' => 'Le nombre de pigeonneaux doit être un nombre entier',
      'nb_pigeonneaux.min' => 'Le nombre de pigeonneaux doit être au minimum 0',
      'nb_pigeonneaux.max' => 'Le nombre de pigeonneaux ne peut pas dépasser le nombre d\'œufs',
      'notes.max' => 'Les notes ne peuvent pas dépasser 1000 caractères',
    ];
  }
}
