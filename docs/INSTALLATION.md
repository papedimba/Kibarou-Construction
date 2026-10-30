# Installation

## 1. En production (archive de livraison)
1. Construire l'archive (sur un poste avec PHP et Composer) : `./bin/build-release.sh 1.0.0` → `dist/kibarou-cms-1.0.0.zip`. Elle contient le code et `vendor/` (production), sans tests, secrets ni données.
2. Envoyer et décompresser l'archive sur le serveur (voir `DEPLOYMENT_CPANEL.md` pour le choix du dossier web).
3. Créer une base MySQL/MariaDB et un utilisateur (jeu de caractères `utf8mb4`).
4. Ouvrir `https://votre-domaine/install.php`, vérifier les prérequis, renseigner base, domaine de la plateforme, super administrateur et (facultatif) premier site.
5. L'installateur crée les tables, écrit `.env` (droits 600, clé `APP_KEY` aléatoire, `APP_DEBUG=0`), pose `storage/installed.lock` et **supprime `install.php`**. Il refuse de se relancer si `.env` ou le verrou existent.
6. Vérifier : `php bin/console.php doctor` (ne doit afficher aucun `[FAIL]`).

## 2. Développement local
```bash
composer install
cp .env.example .env            # renseigner DB_*, PLATFORM_HOST, APP_KEY, MAIL_DRIVER=log
php bin/console.php migrate
php bin/console.php superadmin root@example.com 'mot-de-passe-long' 'Root'
php bin/console.php site:create kibarou 'KIBAROU CONSTRUCTION' kibarou.localhost admin@example.com 'Admin' 'mot-de-passe-12+'
php -S 127.0.0.1:8080 -t public bin/dev-router.php
```
Plateforme : `http://platform.localhost:8080/platform/login` · Site : `http://kibarou.localhost:8080/` (les `*.localhost` sont résolus par les navigateurs modernes).

## 3. Variables `.env`
| Variable | Rôle |
|---|---|
| `APP_ENV` | `production` en ligne ; `local` en développement |
| `APP_DEBUG` | `0` en ligne (les erreurs ne sont pas affichées, elles sont écrites dans `storage/logs/app.log`) |
| `APP_KEY` | Secret (32+ caractères) qui signe les jetons antispam. **Obligatoire en production.** |
| `PLATFORM_HOST` | Domaine de l'administration centrale (`/platform`) |
| `PLATFORM_BASE_DOMAIN` | Domaine de base pour les sous-domaines `<slug>.<base>` (facultatif) |
| `DB_HOST` `DB_PORT` `DB_NAME` `DB_USER` `DB_PASS` | Connexion MySQL/MariaDB |
| `MAIL_DRIVER` | `mail` (fonction `mail()` de PHP) ou `log` (écrit dans `storage/logs/mail.log`) |
| `MAIL_FROM` | Expéditeur des notifications (une adresse de votre domaine) |

## 4. Commandes
`php bin/console.php` : `migrate` · `superadmin <email> <mdp> <nom>` · `site:create …` · `setting <slug> <clé> <valeur>` · `backup [--keep=N]` · `restore <zip> --yes` · `site:purge <slug> --yes` · `doctor`.

## 5. Prérequis serveur
PHP 8.2+ (testé 8.3) avec `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `openssl`, `intl` ; `zip` pour les sauvegardes. MySQL 8 ou MariaDB 10.5+ (testé 10.11). Apache avec `mod_rewrite` (`.htaccess` fournis). Ni Node, ni Docker, ni Redis en production.

## 6. Tests
Voir `TESTING.md`.
