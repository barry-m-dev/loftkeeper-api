<?php

declare(strict_types=1);

namespace Modules\Users\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Modèle PersonalAccessToken
 * Utilise le comportement standard de Laravel Sanctum
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
  // Utiliser le comportement par défaut de Sanctum
  // Pas besoin de personnalisation
}
