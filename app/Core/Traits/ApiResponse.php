<?php

namespace App\Core\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait pour standardiser les réponses API
 * 
 * Fournit des méthodes pour créer des réponses JSON cohérentes
 * avec gestion de la pagination, des erreurs et des métadonnées.
 */
trait ApiResponse
{
  /**
   * Réponse de succès
   */
  public function success(
    mixed $data = null,
    string $message = '',
    int $status = 200,
    array $meta = []
  ): JsonResponse {
    $response = ['success' => true];

    if ($data !== null) {
      if ($data instanceof LengthAwarePaginator) {
        $response['data'] = $data->items();
        $response['pagination'] = [
          'current_page' => $data->currentPage(),
          'per_page' => $data->perPage(),
          'total' => $data->total(),
          'last_page' => $data->lastPage(),
          'from' => $data->firstItem(),
          'to' => $data->lastItem(),
        ];
      } elseif (is_array($data) && isset($data['data'])) {
        $response = array_merge($response, $data);
      } else {
        $response['data'] = $data;
      }
    }

    if (!empty($message)) {
      $response['message'] = $message;
    }

    if (!empty($meta)) {
      $response['meta'] = $meta;
    }

    return response()->json($response, $status);
  }

  /**
   * Réponse d'erreur
   */
  public function error(
    string $message,
    string $errorType = 'error',
    int $status = 400,
    mixed $errors = null,
    array $meta = []
  ): JsonResponse {
    $response = [
      'success' => false,
      'message' => $message,
      'error_type' => $errorType,
    ];

    if ($errors !== null) {
      $response['errors'] = $errors;
    }

    if (!empty($meta)) {
      $response['meta'] = $meta;
    }

    return response()->json($response, $status);
  }

  /**
   * Erreur de validation
   */
  public function validationError(
    array $errors,
    string $message = 'Les données fournies sont invalides.'
  ): JsonResponse {
    return $this->error(
      message: $message,
      errorType: 'validation',
      status: 422,
      errors: $errors
    );
  }

  /**
   * Non autorisé (401)
   */
  public function unauthorized(
    string $message = 'Non autorisé.',
    string $errorType = 'unauthorized'
  ): JsonResponse {
    return $this->error($message, $errorType, 401);
  }

  /**
   * Accès interdit (403)
   */
  public function forbidden(
    string $message = 'Accès interdit.',
    string $errorType = 'forbidden'
  ): JsonResponse {
    return $this->error($message, $errorType, 403);
  }

  /**
   * Ressource non trouvée (404)
   */
  public function notFound(
    string $message = 'Ressource non trouvée.',
    string $errorType = 'not_found'
  ): JsonResponse {
    return $this->error($message, $errorType, 404);
  }

  /**
   * Erreur serveur (500)
   */
  public function serverError(
    string $message = 'Erreur serveur interne.',
    string $errorType = 'server_error'
  ): JsonResponse {
    return $this->error($message, $errorType, 500);
  }

  /**
   * Trop de requêtes (429)
   */
  public function tooManyRequests(
    string $message = 'Trop de requêtes.',
    string $errorType = 'rate_limited',
    ?int $retryAfter = null
  ): JsonResponse {
    $meta = [];
    if ($retryAfter !== null) {
      $meta['retry_after'] = $retryAfter;
    }
    return $this->error($message, $errorType, 429, null, $meta);
  }

  /**
   * Ressource créée (201)
   */
  public function created(
    mixed $data = null,
    string $message = 'Ressource créée avec succès.',
    array $meta = []
  ): JsonResponse {
    return $this->success($data, $message, 201, $meta);
  }

  /**
   * Pas de contenu (204)
   */
  public function noContent(): JsonResponse
  {
    return response()->json(null, 204);
  }
}
