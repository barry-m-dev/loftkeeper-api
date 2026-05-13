<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la requête de vérification OTP
 */
class VerifyOtpRequest extends FormRequest
{
  /**
   * Détermine si l'utilisateur est autorisé à faire cette requête
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Règles de validation
   */
  public function rules(): array
  {
    return [
      'email' => ['required', 'string', 'email'],
      'code' => ['required', 'string', 'size:6'],
    ];
  }

  /**
   * Messages d'erreur personnalisés
   */
  public function messages(): array
  {
    return [
      'email.required' => 'L\'adresse email est obligatoire.',
      'email.email' => 'L\'adresse email doit être valide.',
      'code.required' => 'Le code de validation est obligatoire.',
      'code.size' => 'Le code de validation doit contenir exactement 6 caractères.',
    ];
  }
}
