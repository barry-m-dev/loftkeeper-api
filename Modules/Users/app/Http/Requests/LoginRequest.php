<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la requête de connexion
 * Accepte email OU numéro de téléphone
 */
class LoginRequest extends FormRequest
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
      'identifier' => [
        'required',
        'string',
        'max:255',
      ],
      'password' => [
        'required',
        'string',
        'min:8',
      ],
    ];
  }

  /**
   * Messages d'erreur personnalisés
   */
  public function messages(): array
  {
    return [
      'identifier.required' => 'L\'email ou le numéro de téléphone est requis.',
      'identifier.max' => 'L\'identifiant ne peut pas dépasser 255 caractères.',
      'password.required' => 'Le mot de passe est requis.',
      'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
    ];
  }

  /**
   * Prépare les données avant validation
   */
  protected function prepareForValidation(): void
  {
    $identifier = trim($this->identifier ?? '');

    // Si c'est un email, le mettre en minuscule
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
      $identifier = strtolower($identifier);
    }

    // Si c'est un téléphone, nettoyer les espaces
    if (preg_match('/^\d/', $identifier)) {
      $identifier = preg_replace('/\D/', '', $identifier);
    }

    $this->merge([
      'identifier' => $identifier,
    ]);
  }

  /**
   * Détermine si l'identifiant est un email
   */
  public function isEmail(): bool
  {
    return filter_var($this->identifier, FILTER_VALIDATE_EMAIL) !== false;
  }

  /**
   * Détermine si l'identifiant est un numéro de téléphone
   */
  public function isPhone(): bool
  {
    $digitsOnly = preg_replace('/\D/', '', $this->identifier);
    return !$this->isEmail() && strlen($digitsOnly) >= 9 && strlen($digitsOnly) <= 12;
  }
}
