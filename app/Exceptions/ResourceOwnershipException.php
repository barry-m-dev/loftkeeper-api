<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Exception levée quand un utilisateur tente d'accéder à une ressource qui ne lui appartient pas
 * 
 * @package App\Exceptions
 */
class ResourceOwnershipException extends Exception
{
  /**
   * Constructeur
   * 
   * @param string $resource Nom de la ressource (pigeon, cage, couple, etc.)
   */
  public function __construct(string $resource = 'ressource')
  {
    $message = "Vous n'avez pas accès à cette {$resource}.";
    parent::__construct($message);
  }

  /**
   * Rendre l'exception en réponse JSON
   * 
   * @return JsonResponse
   */
  public function render(): JsonResponse
  {
    return response()->json([
      'success' => false,
      'message' => $this->getMessage(),
      'code' => 'RESOURCE_OWNERSHIP_DENIED',
    ], 403);
  }
}
