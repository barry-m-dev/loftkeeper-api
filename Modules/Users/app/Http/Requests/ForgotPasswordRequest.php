<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la requête de mot de passe oublié
 */
class ForgotPasswordRequest extends FormRequest
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
    ];
  }
}
