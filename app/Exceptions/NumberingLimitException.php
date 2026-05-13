<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Exception levée quand la limite de numérotation est atteinte
 * 
 * Exemple : Impossible de créer plus de 9999 pigeons (P0001 à P9999)
 * 
 * @package App\Exceptions
 */
class NumberingLimitException extends Exception
{
  /**
   * Constructeur
   * 
   * @param string $entity Type d'entité (pigeon, cage, couple)
   * @param int $max Limite maximale
   */
  public function __construct(string $entity, int $max)
  {
    $messages = [
      'pigeon' => "Limite de numérotation atteinte : vous ne pouvez pas créer plus de {$max} pigeons.",
      'cage' => "Limite de numérotation atteinte : vous ne pouvez pas créer plus de {$max} cages.",
      'couple' => "Limite de numérotation atteinte : vous ne pouvez pas créer plus de {$max} couples.",
    ];

    $message = $messages[$entity] ?? "Limite de numérotation atteinte pour {$entity} : maximum {$max}.";

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
      'code' => 'NUMBERING_LIMIT_REACHED',
    ], 422);
  }
}
