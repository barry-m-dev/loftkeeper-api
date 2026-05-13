<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Cross-Origin Resource Sharing (CORS) Configuration
  |--------------------------------------------------------------------------
  |
  | Configuration CORS pour l'API Validation Project
  | Permet les requêtes depuis le frontend React
  |
  */

  'paths' => ['api/*', 'sanctum/csrf-cookie'],

  'allowed_methods' => ['*'],

  'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173')),

  'allowed_origins_patterns' => [],

  'allowed_headers' => ['*'],

  'exposed_headers' => ['X-CSRF-Token'],

  'max_age' => 0,

  // CRITIQUE : Nécessaire pour les cookies HttpOnly avec Sanctum
  'supports_credentials' => true,
];
