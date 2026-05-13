<?php

namespace Modules\Cages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCageRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true; // L'autorisation est gérée par les policies
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'nom' => 'nullable|string|max:100',
      'superficie' => 'nullable|numeric|min:0|max:9999.99',
      'notes' => 'nullable|string|max:500',
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
      'nom.max' => 'Le nom ne peut pas dépasser 100 caractères',
      'superficie.numeric' => 'La superficie doit être un nombre',
      'superficie.min' => 'La superficie doit être positive',
      'superficie.max' => 'La superficie ne peut pas dépasser 9999.99 m²',
      'notes.max' => 'Les notes ne peuvent pas dépasser 500 caractères',
    ];
  }

  /**
   * Get custom attributes for validator errors.
   *
   * @return array<string, string>
   */
  public function attributes(): array
  {
    return [
      'nom' => 'nom de la cage',
      'superficie' => 'superficie',
      'notes' => 'notes',
    ];
  }
}
