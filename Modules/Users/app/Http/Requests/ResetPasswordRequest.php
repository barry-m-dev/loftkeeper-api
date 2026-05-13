<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la requête de réinitialisation de mot de passe
 */
class ResetPasswordRequest extends FormRequest
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
      'token' => ['required', 'string', 'size:6'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
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
      'token.required' => 'Le code de réinitialisation est obligatoire.',
      'token.size' => 'Le code de réinitialisation doit contenir exactement 6 caractères.',
      'password.required' => 'Le nouveau mot de passe est obligatoire.',
      'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
      'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
    ];
  }
}
