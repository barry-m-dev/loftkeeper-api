<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour convertir le cookie access_token en header Authorization Bearer
 * 
 * Le cookie est NON chiffré (raw) car le token Sanctum est déjà sécurisé.
 * Ce middleware extrait simplement le token et le met dans le header.
 */
class ConvertCookieToBearer
{
  /**
   * Handle an incoming request.
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Si le header Authorization existe déjà, ne rien faire
    if ($request->bearerToken()) {
      return $next($request);
    }

    // Récupérer le token depuis le cookie (NON chiffré)
    $token = $request->cookie('access_token');

    if ($token) {
      // Ajouter le token dans le header Authorization
      $request->headers->set('Authorization', 'Bearer ' . $token);

      Log::info('ConvertCookieToBearer - Token added', [
        'url' => $request->url(),
        'token_preview' => substr($token, 0, 20) . '...',
      ]);
    }

    return $next($request);
  }
}
