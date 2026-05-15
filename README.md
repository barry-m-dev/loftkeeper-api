# 🚀 LoftKeeper API - Backend

API REST pour la gestion d'un élevage de pigeons voyageurs.

## 📋 Table des matières

- [Stack Technique](#-stack-technique)
- [Modules Disponibles](#-modules-disponibles)
- [Structure du Projet](#-structure-du-projet)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Lancement](#-lancement)
- [API Documentation](#-api-documentation)

---

## 🛠 Stack Technique

- **Framework** : Laravel 11.x
- **PHP** : 8.2+
- **Base de données** : MySQL 8.0+
- **Architecture** : Modulaire (nwidart/laravel-modules)
- **Authentification** : Laravel Sanctum (API Tokens)
- **Validation** : Form Requests
- **Cache** : Database Driver
- **Queue** : Database Driver
- **Mail** : Brevo API (HTTP)
- **Storage** : Local (public disk)

---

## 📦 Modules Disponibles

L'application est organisée en modules indépendants :

### 1. **Pigeons** (`Modules/Pigeons`)

Gestion complète des pigeons :

- CRUD pigeons (bague, nom, sexe, race, couleur, photo)
- Généalogie (père, mère, enfants)
- Statuts (ACTIF, VENDU, MORT, PERDU)
- Disponibilité (DISPONIBLE, EN_COUPLE, EN_CAGE)
- Numérotation automatique (P0001, P0002...)

### 2. **Cages** (`Modules/Cages`)

Gestion des cages :

- CRUD cages (numéro, nom, superficie)
- Statuts (LIBRE, OCCUPE_PIGEON, OCCUPE_COUPLE)
- Attribution pigeon/couple
- Numérotation automatique (C001, C002...)

### 3. **Couples** (`Modules/Couples`)

Gestion des couples reproducteurs :

- Formation couples (mâle + femelle)
- Attribution cage
- Statuts (ACTIF, ROMPU)
- Historique reproductions
- Numérotation automatique (CP001, CP002...)

### 4. **Reproductions** (`Modules/Reproductions`)

Gestion du cycle de reproduction :

- Enregistrement ponte (1-2 œufs)
- Déclaration éclosion
- Déclaration sevrage
- Enregistrement pigeonneaux
- Statuts (PONTE, ECLOSION, SEVRAGE, ENREGISTRE, ECHEC)
- Statistiques (taux de réussite, total pigeonneaux)

### 5. **Users** (`Modules/Users`)

Gestion des utilisateurs :

- Authentification (email/password)
- Profils utilisateurs
- Isolation des données par utilisateur

---

## 📁 Structure du Projet

```
api/
├── app/
│   ├── Core/
│   │   └── Traits/
│   │       └── BelongsToUser.php      # Trait pour isolation user_id
│   ├── Services/
│   │   └── NumberingService.php       # Service numérotation automatique
│   └── ...
├── Modules/
│   ├── Pigeons/
│   │   ├── app/
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/Api/   # Controllers REST
│   │   │   │   ├── Requests/          # Validation
│   │   │   │   └── Resources/         # Transformation JSON
│   │   │   ├── Models/                # Eloquent Models
│   │   │   └── Services/              # Logique métier
│   │   ├── database/
│   │   │   ├── migrations/            # Migrations DB
│   │   │   └── seeders/               # Données de test
│   │   └── routes/
│   │       └── api.php                # Routes API
│   ├── Cages/
│   ├── Couples/
│   ├── Reproductions/
│   └── Users/
├── config/
│   ├── numbering.php                  # Config numérotation
│   └── ...
├── database/
│   └── seeders/
│       └── DatabaseSeeder.php         # Seeder principal
├── public/
│   └── storage/                       # Stockage public (photos)
├── storage/
│   └── app/
│       └── public/                    # Fichiers uploadés
├── .env.example                       # Variables d'environnement
├── composer.json                      # Dépendances PHP
└── README.md
```

---

## 🚀 Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL 8.0 ou supérieur
- Extension PHP : `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`

### Étapes

1. **Cloner le repository**

```bash
git clone <repository-url>
cd api
```

2. **Installer les dépendances**

```bash
composer install
```

3. **Copier le fichier d'environnement**

```bash
cp .env.example .env
```

4. **Générer la clé d'application**

```bash
php artisan key:generate
```

5. **Créer la base de données**

```sql
CREATE DATABASE loftkeeper_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## ⚙️ Configuration

### 1. Configurer `.env`

Éditer le fichier `.env` avec vos paramètres :

```env
# Application
APP_NAME="LoftKeeper"
APP_URL=http://localhost:8000

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loftkeeper_db
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe

# Mail (Brevo API)
MAIL_MAILER=brevo
BREVO_API_KEY=votre_cle_api_brevo
MAIL_FROM_ADDRESS=votre@email.com
MAIL_FROM_NAME="${APP_NAME}"

# Frontend
FRONTEND_URL=http://localhost:5173

# CORS
CORS_ALLOWED_ORIGINS="http://localhost:5173,http://127.0.0.1:5173"

# Sanctum
SANCTUM_STATEFUL_DOMAINS="localhost,localhost:5173,127.0.0.1,127.0.0.1:5173"
```

### 2. Exécuter les migrations

```bash
php artisan migrate
```

### 3. Créer le lien symbolique pour le storage

```bash
php artisan storage:link
```

### 4. (Optionnel) Générer des données de test

```bash
php artisan db:seed
```

---

## 🎯 Lancement

### Serveur de développement

```bash
php artisan serve
```

L'API sera accessible sur : `http://localhost:8000`

### Vérifier l'installation

```bash
curl http://localhost:8000/api/v1/health
```

---

## 📚 API Documentation

### Base URL

```
http://localhost:8000/api/v1
```

### Authentification

L'API utilise **Laravel Sanctum** pour l'authentification par tokens.

#### Obtenir un token

```http
POST /auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

#### Utiliser le token

```http
GET /pigeons
Authorization: Bearer {token}
```

### Endpoints principaux

#### Pigeons

- `GET /pigeons` - Liste des pigeons
- `POST /pigeons` - Créer un pigeon
- `GET /pigeons/{uuid}` - Détails d'un pigeon
- `PUT /pigeons/{uuid}` - Modifier un pigeon
- `DELETE /pigeons/{uuid}` - Supprimer un pigeon (soft delete)

#### Cages

- `GET /cages` - Liste des cages
- `POST /cages` - Créer une cage
- `GET /cages/{uuid}` - Détails d'une cage
- `PUT /cages/{uuid}` - Modifier une cage
- `DELETE /cages/{uuid}` - Supprimer une cage (soft delete)

#### Couples

- `GET /couples` - Liste des couples
- `POST /couples` - Créer un couple
- `GET /couples/{uuid}` - Détails d'un couple
- `PUT /couples/{uuid}` - Modifier un couple
- `DELETE /couples/{uuid}` - Supprimer un couple (soft delete)
- `POST /couples/{uuid}/rompre` - Rompre un couple

#### Reproductions

- `GET /reproductions` - Liste des reproductions
- `POST /reproductions` - Enregistrer une ponte
- `GET /reproductions/{uuid}` - Détails d'une reproduction
- `PUT /reproductions/{uuid}` - Modifier une reproduction
- `DELETE /reproductions/{uuid}` - Supprimer une reproduction (soft delete)
- `POST /reproductions/{uuid}/eclosion` - Déclarer l'éclosion
- `POST /reproductions/{uuid}/sevrage` - Déclarer le sevrage
- `POST /reproductions/{uuid}/enregistrer-pigeonneaux` - Enregistrer les pigeonneaux

---

## 🔒 Sécurité

- **Isolation des données** : Chaque utilisateur ne voit que ses propres données (Global Scope `UserScope`)
- **Validation** : Toutes les entrées sont validées via Form Requests
- **Soft Delete** : Les suppressions sont logiques (pas de perte de données)
- **CORS** : Configuration stricte des origines autorisées
- **Sanctum** : Tokens d'authentification sécurisés avec expiration

---

## 🧪 Tests

```bash
# Exécuter les tests
php artisan test

# Avec couverture
php artisan test --coverage
```

---

## 📝 Commandes Artisan Utiles

```bash
# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Lister les routes
php artisan route:list

# Créer un nouveau module
php artisan module:make NomModule

# Générer un controller dans un module
php artisan module:make-controller NomController NomModule

# Rafraîchir la base de données
php artisan migrate:fresh --seed
```

---

## 🤝 Contribution

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

---

## 📄 Licence

Ce projet est sous licence privée.

---

## 👥 Auteurs

- **Équipe LoftKeeper** - Développement initial

---

## 🆘 Support

Pour toute question ou problème, veuillez ouvrir une issue sur le repository.
