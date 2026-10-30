# KIBAROU CMS

CMS multisite en PHP natif (8.2+), MySQL/MariaDB, sans framework ni service externe obligatoire. Premier site : KIBAROU CONSTRUCTION (BTP et vente de terrains, Côte d'Ivoire).

**État** : plan de développement complet (lots 1 à 7), 133 tests PHPUnit, 9 scénarios Playwright, 41 contrôles de déploiement sur Apache. Écarts et travaux restants : [`docs/CONFORMITE.md`](docs/CONFORMITE.md).

## Démarrer
- **Installer en production** : [`docs/INSTALLATION.md`](docs/INSTALLATION.md) et [`docs/DEPLOYMENT_CPANEL.md`](docs/DEPLOYMENT_CPANEL.md) (`./bin/build-release.sh`, puis `install.php`).
- **Développer en local** : `composer install`, `.env`, `php bin/console.php migrate`, `php -S 127.0.0.1:8080 -t public bin/dev-router.php`.

## Documentation
[Installation](docs/INSTALLATION.md) · [Déploiement cPanel](docs/DEPLOYMENT_CPANEL.md) · [Architecture](docs/ARCHITECTURE.md) · [Base de données](docs/DATABASE.md) · [Sécurité](docs/SECURITY.md) · [Guide utilisateur](docs/USER_GUIDE.md) · [Tests](docs/TESTING.md) · [Design](docs/DESIGN.md) · [Conformité](docs/CONFORMITE.md)
