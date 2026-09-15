# Cité Étoile du Monde

Application de gestion immobilière de **MJIC IMMOBILIER SARL**.

## Stack

- PHP 8.5 et Laravel 13.
- Blade, JavaScript et Vite.
- MySQL 8.x, InnoDB et utf8mb4.
- Pest 5 pour les tests.

Les versions exactes des dépendances sont verrouillées dans `composer.lock` et `package-lock.json`.

## Références fonctionnelles

- [Cahier des charges](Cahier_des_charges_fonctionnel_App_Gestion_Cite_Etoile_du_Monde_MySQL.pdf), avec le schéma MySQL en annexe D.
- [Plan de développement](Plan_developpement_TODO_Cite_Etoile_du_Monde_MySQL.pdf).

Le développement suit une tâche du plan à la fois, avec vérification et validation avant de poursuivre.

## Installation locale

Prérequis : PHP 8.5 avec `pdo_mysql`, Composer, Node.js/npm compatibles avec Vite 8 et MySQL 8.x.

```bash
composer install
npm ci
```

Pour une nouvelle installation, copier `.env.example` vers `.env` sans écraser une configuration existante, puis renseigner les paramètres MySQL. Créer la base locale avec l’encodage `utf8mb4` et la collation `utf8mb4_unicode_ci`.

```bash
php artisan key:generate --no-interaction
php artisan migrate --no-interaction
npm run build
php artisan serve
```

La génération de clé concerne uniquement une nouvelle installation : conserver la clé existante d’une application contenant des données chiffrées.

Pour travailler sur les assets, lancer `npm run dev` dans un second terminal.

## Vérifications

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
composer analyse
```

`composer format` applique le style Laravel avec Pint. `composer analyse` exécute Larastan au niveau 6 sur le code applicatif et la base de données. `composer quality` enchaîne formatage, analyse statique et tests avant un commit. L’analyse est volontairement séquentielle afin de rester déterministe et de fonctionner dans les environnements qui interdisent les serveurs TCP internes.

La suite utilise la base MySQL dédiée `cite_etoile_du_monde_testing`. Vérifier séparément la connexion locale de développement avec `php artisan db:show --no-interaction`.

## Journalisation technique

Les environnements local et production utilisent des logs JSON quotidiens avec une rétention de 30 jours. La production écrit également sur `stderr` pour l’intégration aux plateformes d’hébergement. Les clés sensibles présentes dans le contexte structuré, y compris les tableaux imbriqués et les placeholders PSR-3, sont remplacées par `[REDACTED]`. Ne jamais placer volontairement un secret directement dans le texte libre d’un message de log.

Les logs techniques servent au diagnostic des erreurs et du fonctionnement de l’application. L’audit des actions métier sensibles sera implémenté séparément dans la phase prévue par le plan.

## Contribution et branches

- `main` est la branche d’intégration des tâches validées.
- Créer une branche courte par tâche : `feat/p0-03-environments`, `fix/p0-01-mysql` ou `docs/p0-02-contribution`.
- Garder chaque changement limité à la tâche en cours.
- Relire les changements, exécuter les vérifications pertinentes et obtenir la validation avant fusion dans `main`.
- Consulter `AGENTS.md` et les règles applicables avant toute modification.

Après le premier commit explicitement autorisé, créer une branche avec :

```bash
git switch -c feat/p0-03-environments
```

Le dépôt initial est sur `main`, sans commit. Les branches de travail seront créées au début de chaque tâche après ce premier commit. Aucun dépôt distant ni mécanisme de protection distant n’est configuré à cette étape.

## Messages de commit

Format : `type(scope): description`, avec une description courte à l’impératif en anglais.

Types : `feat`, `fix`, `test`, `docs`, `refactor`, `chore`.

Exemples :

```text
chore(project): initialize Laravel and MySQL configuration
docs(project): document contribution workflow
fix(database): correct MySQL connection settings
```

Un commit doit représenter un changement cohérent. Les agents ne créent aucun commit et ne publient rien sans instruction explicite.

## Secrets et données locales

- Conserver les secrets dans `.env`, jamais dans le code ou les messages de commit.
- `.env.example` contient uniquement des exemples sans secret.
- Les variantes `.env.*`, clés privées, dépendances, logs et certains formats de sauvegarde sont exclus de Git.
- Les fichiers SQL source restent versionnables pour permettre le suivi du schéma validé. Ne jamais ajouter un export contenant des données clients ou financières.
- Avant un commit, sélectionner les fichiers explicitement et examiner `git diff --cached` ainsi que `git status --short`.
- Une exclusion Git n’est pas un détecteur de secrets : relire aussi les fichiers de configuration et documents ajoutés.

## Environnements

- **Local** : `.env.example` sert de modèle à `.env`. Conserver la configuration MySQL et la clé locales déjà présentes. Le débogage est activé pour le développement.
- **Test** : `.env.testing.example` sert de modèle à `.env.testing`. `phpunit.xml` impose la base MySQL `cite_etoile_du_monde_testing`, tandis que le cache et les sessions restent en mémoire, les mails sans envoi et les tâches synchrones. Le compte `cite_etoile_testing` doit avoir accès uniquement à cette base.
- **Production** : `.env.production.example` est un modèle sans secrets, avec `APP_ENV=production`, `APP_DEBUG=false`, cookies HTTPS/HttpOnly et sessions chiffrées. Le domaine `example.invalid` doit être remplacé. Le transport mail `array` désactive volontairement tout envoi tant que le transport réel n’est pas configuré.

Les trois modèles sont versionnés ; leurs copies contenant des secrets sont ignorées par Git. Utiliser des bases, comptes MySQL et clés distincts selon l’environnement. Ne jamais copier la clé ou les identifiants de production dans les tests.

Sur une nouvelle installation de test seulement, créer la base et le compte MySQL dédiés, copier le modèle vers `.env.testing`, renseigner son mot de passe, puis générer sa clé avec `php artisan key:generate --env=testing --no-interaction`. Ne pas écraser un fichier existant. Le bootstrap des tests refuse toute base ou tout utilisateur qui ne correspond pas aux identifiants dédiés.

Sur le serveur de production, utiliser le modèle comme `.env` ou fournir les variables via l’hébergeur. Renseigner la clé, les identifiants de base et le domaine HTTPS avant le démarrage ; ne pas utiliser le compte MySQL `root`. Aucun fichier de production réel n’est créé dans le poste de développement.

Laravel charge `.env` par défaut ; en console, `--env=testing` sélectionne `.env.testing` s’il existe. Les variables définies par le processus peuvent primer sur les fichiers. Après un changement local de configuration, exécuter `php artisan config:clear --no-interaction`. Le cache de configuration de production sera généré sur le serveur cible lors du déploiement ; ne pas le générer sur le poste local pour simuler la production.
