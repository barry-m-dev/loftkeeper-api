<?php

namespace App\Services;

use App\Exceptions\NumberingLimitException;
use Illuminate\Support\Facades\DB;

/**
 * Service de génération automatique de numéros
 * 
 * Génère des numéros séquentiels pour les pigeons (P0001), cages (C001) et couples (CP001).
 * Thread-safe grâce à l'utilisation de transactions et lockForUpdate().
 * Chaque utilisateur a sa propre séquence de numéros.
 * 
 * @package App\Services
 */
class NumberingService
{
  /**
   * Génère un numéro pour une entité donnée
   * 
   * @param string $type Type d'entité (pigeon, cage, couple)
   * @param int $userId ID de l'utilisateur
   * @return string Numéro généré (ex: P0001, C001, CP001)
   * @throws NumberingLimitException Si la limite est atteinte
   */
  public function generate(string $type, int $userId): string
  {
    return DB::transaction(function () use ($type, $userId) {
      $config = $this->getConfig($type);
      $lastNumber = $this->getLastNumber($config, $userId);

      // Vérifier si la limite est atteinte
      if ($lastNumber >= $config['max']) {
        throw new NumberingLimitException($type, $config['max']);
      }

      // Générer le nouveau numéro
      $nextNumber = $lastNumber + 1;
      $formattedNumber = $config['prefix'] . str_pad($nextNumber, $config['digits'], '0', STR_PAD_LEFT);

      return $formattedNumber;
    });
  }

  /**
   * Génère une bague pour un pigeon
   * 
   * @param int $userId ID de l'utilisateur
   * @return string Bague générée (ex: P0001)
   */
  public function generateBague(int $userId): string
  {
    return $this->generate('pigeon', $userId);
  }

  /**
   * Génère un numéro pour une cage
   * 
   * @param int $userId ID de l'utilisateur
   * @return string Numéro généré (ex: C001)
   */
  public function generateCageNumero(int $userId): string
  {
    return $this->generate('cage', $userId);
  }

  /**
   * Génère un code pour un couple
   * 
   * @param int $userId ID de l'utilisateur
   * @return string Code généré (ex: CP001)
   */
  public function generateCoupleCode(int $userId): string
  {
    return $this->generate('couple', $userId);
  }

  /**
   * Récupère le dernier numéro utilisé pour un type d'entité
   * 
   * @param array $config Configuration de l'entité
   * @param int $userId ID de l'utilisateur
   * @return int Dernier numéro (0 si aucun)
   */
  protected function getLastNumber(array $config, int $userId): int
  {
    $model = $config['model'];
    $column = $config['column'];
    $prefix = $config['prefix'];
    $prefixLength = strlen($prefix);

    // Récupérer le dernier enregistrement avec lockForUpdate pour éviter les race conditions
    $lastRecord = $model::withoutGlobalScopes()
      ->where('user_id', $userId)
      ->where($column, 'like', $prefix . '%')
      ->orderByRaw("CAST(SUBSTRING({$column}, ?) AS UNSIGNED) DESC", [$prefixLength + 1])
      ->lockForUpdate()
      ->first();

    if (!$lastRecord) {
      return 0;
    }

    // Extraire le numéro (ex: P0001 → 1, C042 → 42)
    $numberPart = substr($lastRecord->{$column}, $prefixLength);
    return (int) $numberPart;
  }

  /**
   * Récupère la configuration pour un type d'entité
   * 
   * @param string $type Type d'entité (pigeon, cage, couple)
   * @return array Configuration
   * @throws \InvalidArgumentException Si le type n'existe pas
   */
  protected function getConfig(string $type): array
  {
    $config = config("numbering.{$type}");

    if (!$config) {
      throw new \InvalidArgumentException("Type de numérotation invalide : {$type}");
    }

    return $config;
  }

  /**
   * Vérifie si un numéro est déjà utilisé
   * 
   * @param string $type Type d'entité (pigeon, cage, couple)
   * @param string $value Valeur à vérifier
   * @param int $userId ID de l'utilisateur
   * @return bool True si déjà utilisé
   */
  public function isAlreadyUsed(string $type, string $value, int $userId): bool
  {
    $config = $this->getConfig($type);
    $model = $config['model'];
    $column = $config['column'];

    return $model::withoutGlobalScopes()
      ->where('user_id', $userId)
      ->where($column, $value)
      ->exists();
  }
}
