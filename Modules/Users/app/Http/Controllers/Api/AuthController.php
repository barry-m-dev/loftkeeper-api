<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers\Api;

use App\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Users\Http\Requests\ForgotPasswordRequest;
use Modules\Users\Http\Requests\LoginRequest;
use Modules\Users\Http\Requests\RegisterRequest;
use Modules\Users\Http\Requests\ResetPasswordRequest;
use Modules\Users\Http\Requests\VerifyOtpRequest;
use Modules\Users\Services\AuthService;

/**
 * Contrôleur d'authentification API
 * 
 * Flow d'authentification avec 2FA :
 * 1. POST /auth/register → Inscription, envoie OTP
 * 2. POST /auth/verify-otp → Vérifie OTP, active compte, retourne tokens
 * 3. POST /auth/login → Connexion, envoie OTP
 * 4. POST /auth/verify-otp → Vérifie OTP, retourne tokens
 * 5. POST /auth/resend-otp → Renvoie un nouveau code OTP
 * 6. POST /auth/refresh → Renouvelle access_token via refresh_token
 * 7. POST /auth/logout → Révoque tokens et supprime cookies
 * 
 * Sécurité :
 * - Tokens dans cookies HttpOnly (inaccessibles en JS)
 * - Access token : 1 heure
 * - Refresh token : 7 jours
 * - CSRF protection via SameSite cookies
 */
class AuthController extends Controller
{
  use ApiResponse;

  private const LOGIN_RATE_LIMIT = 5;
  private const OTP_RATE_LIMIT = 5;

  public function __construct(
    private readonly AuthService $authService
  ) {}

  /**
   * Inscription d'un nouvel utilisateur
   */
  public function register(RegisterRequest $request): JsonResponse
  {
    $result = $this->authService->register($request->validated());

    if (!$result['success']) {
      return $this->error($result['message'], 'registration_failed', 400);
    }

    $user = $result['user'];

    return $this->success([
      'user' => [
        'id' => $user->uuid,
        'email' => $user->email,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
      ],
      'two_factor_required' => false,
    ], 'Inscription réussie. Vous pouvez maintenant vous connecter.', 201);
  }

  /**
   * Connexion avec email OU téléphone + password
   * Envoie OTP et retourne les infos utilisateur
   */
  public function login(LoginRequest $request): JsonResponse
  {
    $throttleKey = $this->getThrottleKey($request->identifier, $request->ip());

    // Vérifier le rate limiting
    if (RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_RATE_LIMIT)) {
      $seconds = RateLimiter::availableIn($throttleKey);
      return $this->tooManyRequests(
        "Trop de tentatives de connexion. Veuillez patienter {$seconds} secondes.",
        'rate_limited',
        $seconds
      );
    }

    // Valider les credentials (email ou téléphone)
    $result = $this->authService->validateCredentials(
      $request->identifier,
      $request->password,
      $request->isEmail()
    );

    if (!$result['success']) {
      RateLimiter::hit($throttleKey, 60);

      $status = match ($result['error_type']) {
        'account_suspended' => 403,
        default => 401,
      };

      return $this->error(
        $result['message'],
        $result['error_type'],
        $status
      );
    }

    $user = $result['user'];

    // Réinitialiser le rate limiter après succès
    RateLimiter::clear($throttleKey);

    // Si l'OTP n'est pas requis, on connecte directement
    if (!$result['two_factor_required']) {
      // Créer les tokens
      $tokens = $this->authService->createAuthTokens($user);

      return $this->success([
        'user' => $this->authService->getUserData($user),
        'access_token' => $tokens['access_token'],
        'refresh_token' => $tokens['refresh_token'],
        'two_factor_required' => false,
      ], 'Connexion réussie.');
    }

    // Sinon, envoyer le code OTP
    if (!$this->authService->sendOtpCode($user)) {
      return $this->serverError(
        'Impossible d\'envoyer le code de validation. Veuillez réessayer.'
      );
    }

    return $this->success([
      'user' => [
        'id' => $user->uuid,
        'email' => $user->email,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
      ],
      'two_factor_required' => true,
    ], 'Un code de validation a été envoyé à votre adresse email.');
  }

  /**
   * Vérification du code OTP
   * Crée les tokens et les retourne dans des cookies HttpOnly
   */
  public function verifyOtp(VerifyOtpRequest $request): JsonResponse
  {
    $throttleKey = 'otp-verify:' . $request->ip();

    // Vérifier le rate limiting
    if (RateLimiter::tooManyAttempts($throttleKey, self::OTP_RATE_LIMIT)) {
      $seconds = RateLimiter::availableIn($throttleKey);
      return $this->tooManyRequests(
        "Trop de tentatives. Veuillez patienter {$seconds} secondes.",
        'rate_limited',
        $seconds
      );
    }

    // Trouver l'utilisateur
    $user = $this->authService->findUserByEmail($request->email);

    if (!$user) {
      return $this->error(
        'Aucun compte trouvé avec cette adresse email.',
        'user_not_found',
        404
      );
    }

    // Vérifier le code OTP
    if (!$this->authService->verifyOtpCode($user, $request->code)) {
      RateLimiter::hit($throttleKey, 60);

      return $this->error(
        'Le code de validation est incorrect ou a expiré.',
        'invalid_code',
        401
      );
    }

    // Réinitialiser le rate limiter
    RateLimiter::clear($throttleKey);

    // Créer les tokens (access + refresh)
    $tokens = $this->authService->createAuthTokens($user);

    // Retourner user + tokens dans le body
    return $this->success([
      'user' => $this->authService->getUserData($user),
      'access_token' => $tokens['access_token'],
      'refresh_token' => $tokens['refresh_token'],
    ], 'Authentification réussie.');
  }

  /**
   * Renvoyer un nouveau code OTP
   */
  public function resendOtp(Request $request): JsonResponse
  {
    $request->validate([
      'email' => 'required|email',
    ]);

    // Trouver l'utilisateur
    $user = $this->authService->findUserByEmail($request->email);

    if (!$user) {
      return $this->error(
        'Aucun compte trouvé avec cette adresse email.',
        'user_not_found',
        404
      );
    }

    // Envoyer le nouveau code
    if (!$this->authService->sendOtpCode($user)) {
      return $this->serverError(
        'Impossible d\'envoyer le code de validation. Veuillez réessayer.'
      );
    }

    return $this->success(
      null,
      'Un nouveau code de validation a été envoyé à votre adresse email.'
    );
  }

  /**
   * Déconnexion (révoque les tokens et supprime les cookies)
   */
  public function logout(): JsonResponse
  {
    Log::info('Logout - Start', [
      'auth_check' => auth()->check(),
      'auth_guard' => config('auth.defaults.guard'),
      'has_bearer' => request()->bearerToken() !== null,
      'bearer_preview' => request()->bearerToken() ? substr(request()->bearerToken(), 0, 30) . '...' : null,
    ]);

    $user = auth()->user();

    Log::info('Logout - User retrieved', [
      'user_found' => $user !== null,
      'user_id' => $user?->id,
      'user_email' => $user?->email,
    ]);

    if ($user) {
      $this->authService->logout($user);
      Log::info('Logout - Service called', ['user_id' => $user->id]);
    } else {
      Log::warning('Logout - No authenticated user found');
    }

    return $this->success(null, 'Déconnexion réussie.');
  }

  /**
   * Récupère les informations de l'utilisateur connecté
   */
  public function me(): JsonResponse
  {
    $user = auth()->user();

    if (!$user) {
      return $this->unauthorized('Non authentifié.');
    }

    return $this->success([
      'user' => $this->authService->getUserData($user),
    ]);
  }

  /**
   * Rafraîchit le token d'authentification via refresh_token dans Authorization header
   */
  public function refresh(Request $request): JsonResponse
  {
    $refreshToken = $request->bearerToken();

    if (!$refreshToken) {
      return $this->unauthorized('Session expirée. Veuillez vous reconnecter.');
    }

    // Valider le refresh token et obtenir l'utilisateur
    $result = $this->authService->validateRefreshToken($refreshToken);

    if (!$result['valid']) {
      return $this->unauthorized($result['error']);
    }

    $user = $result['user'];

    // Révoquer les anciens tokens
    $this->authService->revokeAllTokens($user);

    // Créer de nouveaux tokens
    $tokens = $this->authService->createAuthTokens($user);

    return $this->success([
      'user' => $this->authService->getUserData($user),
      'access_token' => $tokens['access_token'],
      'refresh_token' => $tokens['refresh_token'],
    ], 'Session renouvelée avec succès.');
  }

  /**
   * Demande de réinitialisation de mot de passe
   */
  public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
  {
    $email = $request->email;

    // Vérifier si l'utilisateur existe
    $user = $this->authService->findUserByEmail($email);

    if (!$user) {
      return $this->error(
        'Aucun compte n\'est associé à cette adresse email.',
        'user_not_found',
        404
      );
    }

    // Vérifier si le compte est actif
    if ($user->status === 'suspended') {
      return $this->error(
        'Ce compte a été suspendu. Veuillez contacter l\'administrateur.',
        'account_suspended',
        403
      );
    }

    // Envoyer l'email de réinitialisation
    if (!$this->authService->sendPasswordResetEmail($user)) {
      return $this->serverError(
        'Impossible d\'envoyer l\'email de réinitialisation. Veuillez réessayer plus tard.'
      );
    }

    return $this->success(
      null,
      'Un code de réinitialisation a été envoyé à votre adresse email. Vérifiez votre boîte de réception.'
    );
  }

  /**
   * Réinitialisation du mot de passe
   */
  public function resetPassword(ResetPasswordRequest $request): JsonResponse
  {
    $result = $this->authService->resetPassword(
      $request->email,
      $request->token,
      $request->password
    );

    if (!$result['success']) {
      return $this->error(
        $result['message'],
        'reset_failed',
        400
      );
    }

    return $this->success(null, $result['message']);
  }

  /**
   * Génère une clé unique pour le rate limiting
   */
  private function getThrottleKey(string $email, string $ip): string
  {
    return 'login:' . Str::transliterate(Str::lower($email)) . '|' . $ip;
  }
}
