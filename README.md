# Application de Gestion de Restaurants

## Fonctionnalités

### Accès Public
- Consulter la liste des restaurants
- Voir les détails d'un restaurant (menus, tables, avis)
- Créer un compte
- Se connecter

### Espace Client
- Gérer son profil
- Consulter la liste des restaurants
- Effectuer des réservations
- Consulter l'historique des réservations
- Modifier ou annuler ses réservations

### Espace Administrateur
- Gérer les restaurants (CRUD)
- Gérer les catégories de restaurants
- Gérer les menus et les plats
- Gérer les tables
- Valider les avis clients
- Gérer les réservations
- Consulter la liste des utilisateurs

## Accès Administrateur
Pour accéder à l'interface d'administration, utilisez :
- **Email**: admin@restaurant.fr
- **Mot de passe**: password

## Installation du Projet

1. Cloner le projet :
2. Installer les dépendances : `composer install`
3. Créer un fichier `.env.local` à la racine du projet et configurer les variables d'environnement 
4. Configurer votre base de données
5. Exécuter les migrations : `php bin/console doctrine:migrations:migrate`
6. Charger les fixtures (optionnel) : `php bin/console doctrine:fixtures:load`
7. Démarrer le serveur local : `symfony serve`
8. Builder les assets (tailwind) : `npm install && npm run watch`

## Exigences Techniques
- PHP 8.1 ou supérieur
- Symfony 7.2.x
- PostgreSQL 17.x
- Composer
