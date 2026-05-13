<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Modules\Users\Mail\PasswordResetMail;
use Modules\Users\Mail\TwoFactorCode;
use Modules\Users\Mail\WelcomeUserMail;
use Modules\Users\Models\User;

/**
 * Service d'authentification pour le module Users
 * Gère le flow complet : register, login, 2FA, logout, password reset
 */
class AuthService
{
  /**
   * Durée de validité du code OTP (minutes)
   */
  private const OTP_EXPIRATION_MINUTES = 10;

  /**
   * Inscription d'un nouvel utilisateur
   * 
   * @param array $data Données d'inscription
   * @return array{success: bool, user?: User, message?: string}
   */
  public function register(array $data): array
  {
    try {
      // Vérifier si l'email existe déjà
      if (User::where('email', $data['email'])->exists()) {
        return [
          'success' => false,
          'message' => 'Cette adresse email est déjà utilisée.',
        ];
      }

      // Créer l'utilisateur
      $user = User::create([
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'email' => $data['email'],
        'phone' => $data['phone'] ?? null,
        'password' => Hash::make($data['password']),
        'role' => 'CLIENT', // S'assurer que le rôle est bien renseigné dans la BD
        'status' => 'active', // Actif immédiatement, l'OTP sera géré à la première connexion
        'two_factor_enabled' => true,
        'otp_required' => true, // L'OTP sera requis à la première connexion
      ]);

      // Envoyer l'email de bienvenue
      try {
        Mail::to($user)->send(new WelcomeUserMail($user));
      } catch (\Exception $e) {
        // On le trace dans les logs si ça échoue
        Log::error('Welcome email failed', ['user_uuid' => $user->uuid, 'error' => $e->getMessage()]);
      }

      Log::info('User registered', ['user_uuid' => $user->uuid]);

      return [
        'success' => true,
        'user' => $user,
      ];
    } catch (\Exception $e) {
      Log::error('Registration failed', ['error' => $e->getMessage()]);

      return [
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'inscription.',
      ];
    }
  }

  /**
   * Première étape : validation des credentials
   * 
   * @param string $identifier Email ou téléphone de l'utilisateur
   * @param string $password Mot de passe
   * @param bool $isEmail True si l'identifiant est un email
   * @return array{success: bool, user?: User, two_factor_required?: bool, error_type?: string, message?: string}
   */
  public function validateCredentials(string $identifier, string $password, bool $isEmail = true): array
  {
    // Recherche par email ou téléphone
    $user = $isEmail
      ? User::where('email', $identifier)->first()
      : User::where('phone', $identifier)->first();

    if (!$user) {
      $field = $isEmail ? 'adresse email' : 'numéro de téléphone';
      return [
        'success' => false,
        'error_type' => $isEmail ? 'email' : 'phone',
        'message' => "Aucun compte trouvé avec cette {$field}.",
      ];
    }

    if (!Hash::check($password, $user->password)) {
      return [
        'success' => false,
        'error_type' => 'password',
        'message' => 'Le mot de passe est incorrect.',
      ];
    }

    if ($user->status === 'suspended') {
      return [
        'success' => false,
        'error_type' => 'account_suspended',
        'message' => 'Ce compte a été suspendu. Veuillez contacter l\'administrateur.',
      ];
    }

    // Vérifier si l'utilisateur a le rôle CLIENT
    if ($user->role !== 'CLIENT' && !$user->hasRole('CLIENT')) {
      return [
        'success' => false,
        'error_type' => 'unauthorized_role',
        'message' => 'Accès refusé. Cette zone est réservée aux clients.',
      ];
    }

    return [
      'success' => true,
      'user' => $user,
      'two_factor_required' => (bool) $user->otp_required,
    ];
  }

  /**
   * Vérifie si un utilisateur existe avec cet email
   */
  public function findUserByEmail(string $email): ?User
  {
    return User::where('email', $email)->first();
  }

  /**
   * Génère et envoie le code OTP par email
   */
  public function sendOtpCode(User $user): bool
  {
    try {
      // Générer un code à 6 chiffres
      $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

      // Sauvegarder le code et l'expiration
      $user->update([
        'two_factor_code' => Hash::make($code),
        'two_factor_expires_at' => now()->addMinutes(self::OTP_EXPIRATION_MINUTES),
      ]);

      // Envoyer l'email
      Mail::to($user)->send(new TwoFactorCode($user, $code));

      Log::info('OTP code sent', ['user_uuid' => $user->uuid]);

      return true;
    } catch (\Exception $e) {
      Log::error('Failed to send OTP code', [
        'user_uuid' => $user->uuid,
        'error' => $e->getMessage(),
      ]);

      return false;
    }
  }

  /**
   * Envoie l'email de réinitialisation de mot de passe
   */
  public function sendPasswordResetEmail(User $user): bool
  {
    try {
      // Générer un token unique
      $token = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

      // Sauvegarder le token et l'expiration
      $user->update([
        'two_factor_code' => Hash::make($token),
        'two_factor_expires_at' => now()->addMinutes(self::OTP_EXPIRATION_MINUTES),
      ]);

      // Envoyer l'email
      Mail::to($user)->send(new PasswordResetMail($user, $token));

      Log::info('Password reset email sent', ['user_uuid' => $user->uuid]);

      return true;
    } catch (\Exception $e) {
      Log::error('Failed to send password reset email', [
        'user_uuid' => $user->uuid,
        'error' => $e->getMessage(),
      ]);

      return false;
    }
  }

  /**
   * Vérifie le code OTP
   */
  public function verifyOtpCode(User $user, string $code): bool
  {
    // Vérifier si le code a expiré
    if (!$user->two_factor_expires_at || now()->gt($user->two_factor_expires_at)) {
      return false;
    }

    // Vérifier le code
    if (!Hash::check($code, $user->two_factor_code)) {
      return false;
    }

    // Activer le compte si c'était une inscription
    if ($user->status === 'inactive') {
      $user->update(['status' => 'active']);
    }

    // Nettoyer le code OTP et désactiver l'obligation pour la prochaine fois
    $user->update([
      'two_factor_code' => null,
      'two_factor_expires_at' => null,
      'otp_required' => false,
    ]);

    return true;
  }

  /**
   * Vérifie le token de réinitialisation et met à jour le mot de passe
   * 
   * @return array{success: bool, message: string}
   */
  public function resetPassword(string $email, string $token, string $newPassword): array
  {
    $user = $this->findUserByEmail($email);

    if (!$user) {
      return [
        'success' => false,
        'message' => 'Aucun compte trouvé avec cette adresse email.',
      ];
    }

    // Vérifier si le token a expiré
    if (!$user->two_factor_expires_at || now()->gt($user->two_factor_expires_at)) {
      return [
        'success' => false,
        'message' => 'Le code de réinitialisation a expiré.',
      ];
    }

    // Vérifier le token
    if (!Hash::check($token, $user->two_factor_code)) {
      return [
        'success' => false,
        'message' => 'Le code de réinitialisation est invalide.',
      ];
    }

    // Mettre à jour le mot de passe
    $user->update([
      'password' => Hash::make($newPassword),
      'two_factor_code' => null,
      'two_factor_expires_at' => null,
    ]);

    // Révoquer tous les tokens existants pour forcer la reconnexion
    $user->tokens()->delete();

    Log::info('Password reset successful', ['user_uuid' => $user->uuid]);

    return [
      'success' => true,
      'message' => 'Votre mot de passe a été réinitialisé avec succès.',
    ];
  }

  /**
   * Crée les tokens d'authentification (access + refresh)
   * 
   * @return array{access_token: string, refresh_token: string}
   */
  public function createAuthTokens(User $user): array
  {
    // Révoquer tous les anciens tokens (Single Session)
    $user->tokens()->delete();

    // Access token (1 heure)
    $accessToken = $user->createToken(
      'access_token',
      ['*'],
      now()->addMinutes(60)
    )->plainTextToken;

    // Refresh token (7 jours) - abilities limitées
    $refreshToken = $user->createToken(
      'refresh_token',
      ['refresh'],
      now()->addDays(7)
    )->plainTextToken;

    Log::info('Auth tokens created', ['user_uuid' => $user->uuid]);

    return [
      'access_token' => $accessToken,
      'refresh_token' => $refreshToken,
    ];
  }

  /**
   * Valide un refresh token et retourne l'utilisateur
   * 
   * @return array{valid: bool, user?: User, error?: string}
   */
  public function validateRefreshToken(string $token): array
  {
    try {
      // Le token est au format "id|token"
      $parts = explode('|', $token, 2);

      if (count($parts) !== 2) {
        return ['valid' => false, 'error' => 'Token invalide.'];
      }

      [$id, $plainToken] = $parts;

      // Utiliser notre modèle personnalisé
      $accessToken = \Modules\Users\Models\PersonalAccessToken::find($id);

      if (!$accessToken) {
        return ['valid' => false, 'error' => 'Session expirée.'];
      }

      // Vérifier le hash du token
      if (!hash_equals($accessToken->token, hash('sha256', $plainToken))) {
        return ['valid' => false, 'error' => 'Token invalide.'];
      }

      // Vérifier que c'est bien un refresh token
      if ($accessToken->name !== 'refresh_token') {
        return ['valid' => false, 'error' => 'Token invalide.'];
      }

      // Vérifier l'expiration
      if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
        $accessToken->delete();
        return ['valid' => false, 'error' => 'Session expirée. Veuillez vous reconnecter.'];
      }

      $user = $accessToken->tokenable;

      if (!$user || $user->status !== 'active') {
        return ['valid' => false, 'error' => 'Compte non disponible.'];
      }

      return ['valid' => true, 'user' => $user];
    } catch (\Exception $e) {
      Log::warning('Invalid refresh token', ['error' => $e->getMessage()]);
      return ['valid' => false, 'error' => 'Session invalide.'];
    }
  }

  /**
   * Déconnecte l'utilisateur (révoque le token actuel)
   */
  public function logout(User $user): void
  {
    $currentToken = $user->currentAccessToken();

    if ($currentToken) {
      // ✅ Utilise la méthode native de Sanctum
      $currentToken->delete();
    }

    Log::info('User logged out', ['user_uuid' => $user->uuid]);
  }

  /**
   * Révoque tous les tokens de l'utilisateur
   */
  public function revokeAllTokens(User $user): void
  {
    // ✅ Utilise la méthode native de Sanctum (gère automatiquement la relation polymorphique)
    $user->tokens()->delete();

    Log::info('All tokens revoked', ['user_uuid' => $user->uuid]);
  }

  /**
   * Récupère les données utilisateur formatées pour l'API
   */
  public function getUserData(User $user): array
  {
    return [
      'id' => $user->uuid,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
      'full_name' => $user->full_name,
      'email' => $user->email,
      'phone' => $user->phone,
      'avatar' => $user->avatar,
      'status' => $user->status,
      'two_factor_enabled' => $user->two_factor_enabled,
    ];
  }
}
