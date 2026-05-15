<?php

namespace Modules\Couples\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validation pour la rupture d'un couple
 * 
 * @package Modules\Couples\Http\Requests
 */
class RompreCoupleRequest extends FormRequest
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
      // Pas de champs spécifiques pour le moment
    ];
  }
}
