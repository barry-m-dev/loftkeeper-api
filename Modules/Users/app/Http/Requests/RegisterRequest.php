<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la requête d'inscription
 */
class RegisterRequest extends FormRequest
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
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
      'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{9,12}$/', 'unique:users,phone'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
    ];
  }

  /**
   * Messages d'erreur personnalisés
   */
  public function messages(): array
  {
    return [
      'first_name.required' => 'Le prénom est obligatoire.',
      'first_name.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
      'last_name.required' => 'Le nom est obligatoire.',
      'last_name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
      'email.required' => 'L\'adresse email est obligatoire.',
      'email.email' => 'L\'adresse email doit être valide.',
      'email.unique' => 'Cette adresse email est déjà utilisée.',
      'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
      'phone.regex' => 'Le format du numéro de téléphone est invalide. Utilisez 9 à 12 chiffres (ex: 77 123 45 67).',
      'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
      'password.required' => 'Le mot de passe est obligatoire.',
      'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
      'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
    ];
  }

  /**
   * Prépare les données avant validation
   */
  protected function prepareForValidation(): void
  {
    // Nettoyer le téléphone (enlever les espaces)
    if ($this->has('phone') && $this->phone) {
      $this->merge([
        'phone' => preg_replace('/\D/', '', $this->phone),
      ]);
    }

    // Mettre l'email en minuscule
    if ($this->has('email')) {
      $this->merge([
        'email' => strtolower(trim($this->email)),
      ]);
    }
  }
}
