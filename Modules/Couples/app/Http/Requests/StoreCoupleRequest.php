<?php

namespace Modules\Couples\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validation pour la création d'un couple
 * Applique les règles RG-C01 à RG-C08
 * 
 * @package Modules\Couples\Http\Requests
 */
class StoreCoupleRequest extends FormRequest
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
      'male_uuid' => [
        'required',
        'string',
        Rule::exists('pigeons', 'uuid')->where(function ($query) {
          $query->where('user_id', $this->user()->id);
        }),
      ],
      'femelle_uuid' => [
        'required',
        'string',
        'different:male_uuid',
        Rule::exists('pigeons', 'uuid')->where(function ($query) {
          $query->where('user_id', $this->user()->id);
        }),
      ],
      'cage_uuid' => [
        'nullable',
        'string',
        Rule::exists('cages', 'uuid')->where(function ($query) {
          $query->where('user_id', $this->user()->id);
        }),
      ],
      'date_formation' => 'nullable|date|before_or_equal:today',
      'notes' => 'nullable|string|max:1000',
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
      'male_uuid.required' => 'Le mâle est obligatoire',
      'male_uuid.exists' => 'Ce mâle n\'existe pas',
      'femelle_uuid.required' => 'La femelle est obligatoire',
      'femelle_uuid.exists' => 'Cette femelle n\'existe pas',
      'femelle_uuid.different' => 'Le mâle et la femelle doivent être différents',
      'cage_uuid.exists' => 'Cette cage n\'existe pas',
      'date_formation.date' => 'La date de formation doit être une date valide',
      'date_formation.before_or_equal' => 'La date de formation ne peut pas être dans le futur',
      'notes.max' => 'Les notes ne peuvent pas dépasser 1000 caractères',
    ];
  }
}
