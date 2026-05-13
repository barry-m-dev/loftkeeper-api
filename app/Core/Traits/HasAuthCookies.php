<?php

declare(strict_types=1);

namespace App\Core\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Trait pour gérer les cookies d'authentification HttpOnly
 * 
 * Utilisé par AuthController pour créer/supprimer les cookies
 * access_token et refresh_token de manière sécurisée.
 */
trait HasAuthCookies
{
  /**
   * Durée de vie du access_token (minutes)
   */
  protected int $accessTokenLifetime = 60; // 1 heure

  /**
   * Durée de vie du refresh_token (minutes)
   */
  protected int $refreshTokenLifetime = 10080; // 7 jours

  /**
   * Crée un cookie HttpOnly sécurisé NON CHIFFRÉ
   * Le token Sanctum est déjà sécurisé (hash SHA256)
   * HttpOnly empêche l'accès JavaScript
   */
  protected function createSecureCookie(
    string $name,
    string $value,
    int $minutes
  ): SymfonyCookie {
    return new SymfonyCookie(
      $name,
      $value,
      $minutes > 0 ? now()->addMinutes($minutes) : 0,
      '/',
      config('session.domain'),
      filter_var(env('SESSION_SECURE_COOKIE', false), FILTER_VALIDATE_BOOLEAN), // Lit directement depuis env
      true, // httpOnly = true (sécurité : inaccessible en JS)
      true, // raw = true (pas de chiffrement Laravel, token Sanctum suffit)
      env('SESSION_SAME_SITE', 'lax') // Lit directement depuis env
    );
  }

  /**
   * Crée le cookie access_token
   */
  protected function createAccessTokenCookie(string $token): SymfonyCookie
  {
    return $this->createSecureCookie(
      'access_token',
      $token,
      $this->accessTokenLifetime
    );
  }

  /**
   * Crée le cookie refresh_token
   */
  protected function createRefreshTokenCookie(string $token): SymfonyCookie
  {
    return $this->createSecureCookie(
      'refresh_token',
      $token,
      $this->refreshTokenLifetime
    );
  }

  /**
   * Supprime les cookies d'authentification
   */
  protected function forgetAuthCookies(JsonResponse $response): JsonResponse
  {
    return $response
      ->withCookie(Cookie::forget('access_token'))
      ->withCookie(Cookie::forget('refresh_token'));
  }

  /**
   * Ajoute les cookies d'authentification à la réponse
   */
  protected function withAuthCookies(
    JsonResponse $response,
    string $accessToken,
    ?string $refreshToken = null
  ): JsonResponse {
    $response = $response->withCookie(
      $this->createAccessTokenCookie($accessToken)
    );

    if ($refreshToken) {
      $response = $response->withCookie(
        $this->createRefreshTokenCookie($refreshToken)
      );
    }

    return $response;
  }
}
