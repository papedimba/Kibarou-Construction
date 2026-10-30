# Tests

- PHPUnit : `vendor/bin/phpunit` (base `kibarou_test`, recréée à chaque exécution — ne touche pas la base de dev).
- E2E navigateur (Playwright, Chromium) : `php -S 127.0.0.1:8080 -t public bin/dev-router.php &` puis `node --test tests/E2E/smoke.cjs`. Captures dans `test-results/`.
  Prérequis : base de dev migrée, super admin et site `kibarou` créés (voir README). Données E2E préfixées `E2E-`.

Couverture : isolation inter-tenants (repository, FK composites, IDOR HTTP), résolution de domaines, suspension, CSRF, force brute, RBAC,
catalogue (filtres/tri/statuts), CRUD terrains, création de site depuis /platform, 5 viewports sans débordement horizontal.

Lot 2 : 15 tests CMS (`tests/Feature/CmsTest.php`) — isolation pages/médias, XSS, slugs réservés, révisions, sitemap, redirections, réglages, upload piégé, rôle éditeur — et un scénario Playwright (page, vrai téléversement multipart, thème, sitemap).

Lot 4 : 16 tests (`tests/Feature/LandBtpTest.php`) — filtre hiérarchique, documents privés/publics/vérifiés, IDOR documents/QR/PDF, photos, similaires, lotissements/parcelles, services inactifs, réalisations, rôles par module — et 2 scénarios Playwright (parcours foncier complet avec vrais téléversements, logo officiel affiché).
