# 🚀 FIX DÉPLOIEMENT RAILWAY - AUTOLOAD MODULES

**Date** : 13 Mai 2026  
**Statut** : ✅ Résolu

---

## 🔴 PROBLÈME

### Contexte

- **Projet** : Laravel 13 avec `nwidart/laravel-modules` (v13)
- **Modules** : Users, Cages, Pigeons, Couples, Reproductions, Sorties
- **Plateforme** : Railway avec Railpack builder
- **Plugin** : `wikimedia/composer-merge-plugin` pour merger les `composer.json` des modules

### Erreur en production

```
Class Modules\Cages\Providers\CagesServiceProvider not found
```

### Cause racine

Railway/Railpack exécute :

```bash
composer install --optimize-autoloader --no-scripts
```

Le flag `--no-scripts` empêche l'exécution des scripts post-install, notamment le `wikimedia/composer-merge-plugin` qui est responsable de merger automatiquement les namespaces des modules dans l'autoload principal.

**Résultat** : Laravel ne trouve pas les ServiceProviders des modules et crash.

---

## ✅ SOLUTION

### Fix appliqué

Ajouter explicitement les namespaces de tous les modules dans la section `autoload` > `psr-4` du `composer.json` racine.

### Fichier modifié : `composer.json`

#### Avant :

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
}
```

#### Après :

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Modules\\Users\\": "Modules/Users/app/",
        "Modules\\Cages\\": "Modules/Cages/app/",
        "Modules\\Pigeons\\": "Modules/Pigeons/app/",
        "Modules\\Couples\\": "Modules/Couples/app/",
        "Modules\\Reproductions\\": "Modules/Reproductions/app/",
        "Modules\\Sorties\\": "Modules/Sorties/app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
}
```

---

## 📁 STRUCTURE DES MODULES

Chaque module suit cette structure :

```
Modules/
├── Users/
│   ├── app/
│   │   ├── Http/
│   │   ├── Models/
│   │   ├── Providers/
│   │   │   ├── UsersServiceProvider.php
│   │   │   ├── RouteServiceProvider.php
│   │   │   └── EventServiceProvider.php
│   │   ├── Services/
│   │   └── Traits/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── composer.json
│   └── module.json
├── Cages/
│   └── app/
│       └── Providers/
│           └── CagesServiceProvider.php
├── Pigeons/
│   └── app/
│       └── Providers/
│           └── PigeonsServiceProvider.php
├── Couples/
│   └── app/
│       └── Providers/
│           └── CouplesServiceProvider.php
├── Reproductions/
│   └── app/
│       └── Providers/
│           └── ReproductionsServiceProvider.php
└── Sorties/
    └── app/
        └── Providers/
            └── SortiesServiceProvider.php
```

**Important** : Le namespace `Modules\NomModule\` pointe vers `Modules/NomModule/app/` car c'est là que se trouvent les classes PHP (Controllers, Models, Providers, etc.).

---

## ✅ VÉRIFICATION

### Commandes exécutées en local :

```bash
# 1. Régénérer l'autoload
composer dump-autoload --no-scripts

# 2. Vérifier que tous les ServiceProviders sont trouvés
php artisan tinker --execute="
echo 'Users: ' . (class_exists('Modules\Users\Providers\UsersServiceProvider') ? 'OK' : 'FAIL') . PHP_EOL;
echo 'Cages: ' . (class_exists('Modules\Cages\Providers\CagesServiceProvider') ? 'OK' : 'FAIL') . PHP_EOL;
echo 'Pigeons: ' . (class_exists('Modules\Pigeons\Providers\PigeonsServiceProvider') ? 'OK' : 'FAIL') . PHP_EOL;
echo 'Couples: ' . (class_exists('Modules\Couples\Providers\CouplesServiceProvider') ? 'OK' : 'FAIL') . PHP_EOL;
echo 'Reproductions: ' . (class_exists('Modules\Reproductions\Providers\ReproductionsServiceProvider') ? 'OK' : 'FAIL') . PHP_EOL;
echo 'Sorties: ' . (class_exists('Modules\Sorties\Providers\SortiesServiceProvider') ? 'OK' : 'FAIL');
"
```

### Résultat :

```
Users: OK
Cages: OK
Pigeons: OK
Couples: OK
Reproductions: OK
Sorties: OK
```

✅ **Tous les modules sont correctement autoloadés !**

---

## 🚀 DÉPLOIEMENT

### Étapes pour déployer sur Railway :

```bash
# 1. Ajouter les fichiers modifiés
git add composer.json composer.lock

# 2. Commit
git commit -m "fix: add module namespaces to autoload for Railway deployment"

# 3. Push
git push origin main
```

Railway va automatiquement :

1. Détecter le push
2. Exécuter `composer install --optimize-autoloader --no-scripts`
3. **Les namespaces des modules sont maintenant dans composer.json** ✅
4. Laravel trouve les ServiceProviders
5. L'application démarre correctement

---

## 🔍 POURQUOI ÇA MARCHE ?

### Avant (avec merge-plugin)

1. `composer install` exécute les scripts post-install
2. `wikimedia/composer-merge-plugin` merge les `Modules/*/composer.json`
3. Les namespaces des modules sont ajoutés dynamiquement à l'autoload
4. ✅ Fonctionne en local

### Problème en production (Railway)

1. `composer install --no-scripts` **ne lance PAS les scripts**
2. Le merge-plugin n'est jamais exécuté
3. Les namespaces des modules ne sont pas dans l'autoload
4. ❌ Laravel ne trouve pas les classes

### Après (namespaces explicites)

1. Les namespaces sont **directement dans composer.json**
2. Pas besoin de scripts post-install
3. `composer install --no-scripts` génère l'autoload avec les modules
4. ✅ Fonctionne partout (local + production)

---

## 📝 NOTES IMPORTANTES

### Le merge-plugin reste utile

Le `wikimedia/composer-merge-plugin` reste configuré dans `composer.json` :

```json
"extra": {
    "merge-plugin": {
        "include": ["Modules/*/composer.json"],
        "recurse": true,
        ...
    }
}
```

**Pourquoi ?**

- En local, il peut merger d'autres sections (dependencies, scripts, etc.)
- Pas de conflit avec les namespaces explicites
- Redondance acceptable pour la compatibilité

### Ajout de nouveaux modules

Si vous ajoutez un nouveau module, **n'oubliez pas** d'ajouter son namespace dans `composer.json` :

```json
"Modules\\NouveauModule\\": "Modules/NouveauModule/app/"
```

Puis :

```bash
composer dump-autoload
```

---

## ✅ CHECKLIST FINALE

- [x] Namespaces des 6 modules ajoutés dans `composer.json`
- [x] `composer dump-autoload` exécuté
- [x] Tous les ServiceProviders trouvés (vérification locale)
- [x] `composer.lock` mis à jour
- [x] Prêt pour le déploiement sur Railway

---

**Date de création** : 13 Mai 2026  
**Auteur** : Kiro AI Assistant
