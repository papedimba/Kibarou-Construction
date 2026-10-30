# Tests

- PHPUnit : `vendor/bin/phpunit` (base `kibarou_test`, recréée à chaque exécution — ne touche pas la base de dev).
- E2E navigateur (Playwright, Chromium) : `php -S 127.0.0.1:8080 -t public bin/dev-router.php &` puis `node --test tests/E2E/smoke.cjs`. Captures dans `test-results/`.
  Prérequis : base de dev migrée, super admin et site `kibarou` créés (voir README). Données E2E préfixées `E2E-`.

Couverture : isolation inter-tenants (repository, FK composites, IDOR HTTP), résolution de domaines, suspension, CSRF, force brute, RBAC,
catalogue (filtres/tri/statuts), CRUD terrains, création de site depuis /platform, 5 viewports sans débordement horizontal.

Lot 2 : 15 tests CMS (`tests/Feature/CmsTest.php`) — isolation pages/médias, XSS, slugs réservés, révisions, sitemap, redirections, réglages, upload piégé, rôle éditeur — et un scénario Playwright (page, vrai téléversement multipart, thème, sitemap).

Lot 4 : 16 tests (`tests/Feature/LandBtpTest.php`) — filtre hiérarchique, documents privés/publics/vérifiés, IDOR documents/QR/PDF, photos, similaires, lotissements/parcelles, services inactifs, réalisations, rôles par module — et 2 scénarios Playwright (parcours foncier complet avec vrais téléversements, logo officiel affiché).

Lot 5 : 22 tests (`tests/Feature/CrmTest.php`) et 1 scénario Playwright (visiteur → administration → CSV → formulaire personnalisé, avec nettoyage de ses données). L'E2E a révélé une régression d'affichage mobile (menu à 5 liens sans retour à la ligne), corrigée.

Lot 3 : 22 tests (`tests/Feature/BlocksTest.php`) — liste blanche, URL, coercition, images inter-sites, profondeur/nombre, ids, listes, stockage, rendu analysé *structurellement* (aucun `script`, attribut `on*` ou `style` pour les 30 blocs avec charge hostile), témoignages, vidéos, blocs de données multi-sites, brouillon/publication/aperçu, charge illisible, CSRF, IDOR, rôles, versions, accueil. Playwright : 1 scénario (construction, imbrication, glisser-déposer réel, réordonnancement, répéteur, aperçu mobile par requêtes de conteneur, enregistrement, aperçu, publication, restauration).

Lot 6 : 1 test PHPUnit (photos d'accueil, isolation par site) et 1 scénario Playwright de design (scènes qui suivent la lecture, compteur en direct, menus mobiles, 5 largeurs sans débordement sur l'accueil et trois écrans d'administration, console sans violation CSP). Passer `CHROME_PATH` vers un script qui ajoute `--no-sandbox` pour lancer `inspect.mjs` en root.

## Lot 7
- `tests/Feature/InstallerTest.php` (6), `BackupTest.php` (5), `UsersTest.php` (8), `OpsTest.php` (9 : données structurées, suppression/purge de site, journal, catégories). Contrôles de mutation faits sur les protections de l'antispam, des URL/images de blocs, des utilisateurs.
- **Déploiement de bout en bout** : `bash tests/deploy-smoke.sh` (Apache + mod_php + MariaDB, en root) construit l'archive, la décompresse comme sur cPanel (racine web = racine du projet), l'installe par l'assistant web, puis vérifie 41 points : fichiers sensibles interdits (403), page d'installation supprimée, `.env` en 600, accueil et plateforme servis, compression et cache, CSP, scripts téléversés non exécutés, listing refusé, `doctor` et `backup` en ligne de commande, sauvegarde non téléchargeable par le web. Ce test a révélé un défaut réel du `.htaccess` (voir ARCHITECTURE).
