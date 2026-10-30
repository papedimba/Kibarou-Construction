# KIBAROU CMS

CMS multisite en PHP natif (8.2+), MySQL/MariaDB, sans framework. Premier site : KIBAROU CONSTRUCTION.
État : **Lots 1 à 5** (multisite, auth/RBAC, CMS, éditeur de blocs, foncier, BTP, réalisations, CRM et formulaires ; reste : design, installateur/déploiement). Périmètre et plan : `docs/ARCHITECTURE.md`.

## Démarrage local
```bash
composer install
cp .env.example .env          # renseigner DB_* et PLATFORM_HOST
php bin/console.php migrate
php bin/console.php superadmin root@example.com 'mot-de-passe-long' 'Root'
php bin/console.php site:create kibarou 'KIBAROU CONSTRUCTION' kibarou.localhost admin@example.com 'Admin' 'mot-de-passe-12+'
php -S 127.0.0.1:8080 -t public bin/dev-router.php
```
Plateforme : `http://platform.localhost:8080/platform/login` · Site : `http://kibarou.localhost:8080/` · Admin : `/admin`.
Tests : `vendor/bin/phpunit` (voir `docs/TESTING.md`).
