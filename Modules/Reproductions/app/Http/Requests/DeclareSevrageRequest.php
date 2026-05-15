<?php

namespace Modules\Reproductions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeclareSevrageRequest extends FormRequest
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
      'date_sevrage' => 'required|date|before_or_equal:today',
      'notes' => 'nullable|string|max:1000',
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'date_sevrage.required' => 'La date de sevrage est obligatoire',
      'date_sevrage.date' => 'La date de sevrage doit être une date valide',
      'date_sevrage.before_or_equal' => 'La date de sevrage ne peut pas être dans le futur',
      'notes.max' => 'Les notes ne peuvent pas dépasser 1000 caractères',
    ];
  }
}
