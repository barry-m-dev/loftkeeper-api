<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Configuration de la numérotation automatique
    |--------------------------------------------------------------------------
    |
    | Configuration centralisée pour la génération automatique des numéros
    | de bagues (pigeons), cages et couples.
    |
    | Chaque entité a :
    | - prefix : Préfixe du numéro (P, C, CP)
    | - digits : Nombre de chiffres (4 pour pigeons, 3 pour cages/couples)
    | - max : Limite maximale
    | - model : Classe du modèle
    | - column : Nom de la colonne contenant le numéro
    |
    */

  'pigeon' => [
    'prefix' => 'P',
    'digits' => 4,
    'max'    => 9999,
    'model'  => \Modules\Pigeons\Models\Pigeon::class,
    'column' => 'bague',
  ],

  'cage' => [
    'prefix' => 'C',
    'digits' => 3,
    'max'    => 999,
    'model'  => \Modules\Cages\Models\Cage::class,
    'column' => 'numero',
  ],

  'couple' => [
    'prefix' => 'CP',
    'digits' => 3,
    'max'    => 999,
    'model'  => \Modules\Couples\Models\Couple::class,
    'column' => 'code',
  ],
];
