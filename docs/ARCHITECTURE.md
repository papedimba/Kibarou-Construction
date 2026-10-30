# Architecture — KIBAROU CMS (Lot 1 : moteur multisite)

## Décisions structurantes
| Décision | Choix | Raison |
|---|---|---|
| Multi-tenant | Base unique, isolation logique par `site_id` | Imposé par le cahier des charges ; compatible hébergement mutualisé |
| Défense en profondeur | 3 couches : (1) `TenantContext` seule source du site, (2) `TenantRepository` impose `site_id` à chaque requête, (3) **clés étrangères composites** `(site_id, id)` en base | Une erreur de code ne suffit pas à croiser deux sites |
| `site_id` client | Jamais lu : retiré des données d'écriture (`TenantRepository::clean`) | Anti-IDOR / mass-assignment |
| Résolution | domaine exact → sans `www.` → `<slug>.<PLATFORM_BASE_DOMAIN>` | Domaines personnalisés + sous-domaines |
| Plateforme | Hôte dédié `PLATFORM_HOST` ; `/platform/*` n'existe pas sur les hôtes des sites (404) | Surface d'attaque séparée |
| Sessions | Cookie lié à l'hôte exact (pas de `domain=`), `HttpOnly`, `SameSite=Lax`, id régénéré au login, appartenance au site revérifiée à chaque requête | Un cookie ne passe pas d'un site à l'autre |
| RBAC | Rôles globaux, affectation par `(site, utilisateur, rôle)` ; super admin = drapeau `users.is_super_admin` | Un utilisateur peut avoir des rôles différents selon le site |
| Dépendances | Aucune en production (PHP natif, PSR-4) ; PHPUnit en dev | cPanel sans Node ni Docker |

## Flux d'une requête
`public/index.php` → `App::handle` → hôte = `PLATFORM_HOST` ? routes plateforme : routes site → `TenantMiddleware` (résout, vérifie le statut, remplit `TenantContext`) → `CsrfMiddleware` → `AuthMiddleware` / `PermissionMiddleware` → contrôleur → repository → vue.

## Composants
`app/Core` (Router, Request, Response, Session, Database/PDO, Container, Migrator, View, Page, App) ·
`app/Multisite` (TenantContext, TenantResolver, DomainManager, TenantProvisioner, Slug) ·
`app/Repositories` (TenantRepository, LandRepository) · `app/Services` (AuthService, Rbac) ·
`app/Middleware` · `app/Controllers/{Platform,Admin,Frontend}`.

## Risques techniques identifiés
| Risque | Statut |
|---|---|
| Fuite inter-tenants via une requête sans `site_id` | Atténué (3 couches) + tests d'isolation. **Règle : tout nouveau repository étend `TenantRepository`** |
| Usurpation d'hôte (`Host` falsifié) | Hôte validé/normalisé ; un hôte inconnu ne mène à aucun site. À durcir côté Apache (vhost par défaut) |
| IP client derrière proxy | `X-Forwarded-For` volontairement ignoré (limitation de débit sur `REMOTE_ADDR`) |
| Hébergement sans `public/` comme racine | Lot livraison : `.htaccess` racine + guide cPanel |
| Migrations MySQL non transactionnelles (DDL) | Sauvegarde avant migration (lot livraison) |
| Documents fonciers sensibles | Stockage hors `public/` (`storage/`) — à implémenter au lot Terrains |

## Plan (lots)
1. **Lot 1 (livré)** : socle, multisite, auth/RBAC, admin plateforme minimale, terrains (CRUD + catalogue filtré), tests.
2. **Lot 2 (livré)** : CMS — pages avec révisions, articles, médias, menus, redirections 301, réglages, SEO (meta, canonical, Open Graph, sitemap.xml, robots.txt), 3 thèmes sélectionnables.
3. Lot 3 : éditeur de blocs (BlockRegistry en liste blanche, JSON versionné).
4. **Lot 4 (livré)** : module foncier complet (photos, documents privés, localisations, lotissements/parcelles, fiche PDF, QR codes) + services BTP + réalisations. *Reste du §10.5/§11 : formulaires de demande et de devis → lot 5 (CRM).*
5. Lot 5 : CRM, formulaires, antispam, export CSV.
6. Lot 6 : design (taste-skill / Impeccable), site Kibarou, SEO/sitemap.
7. Lot 7 : installateur cPanel, sauvegardes, documentation de déploiement, campagne E2E complète.

## Hors périmètre actuel (non fait)
Tout ce qui figure aux lots 2 à 7. Le logo officiel est dans `public/assets/images/brand/` mais pas encore intégré aux gabarits.

## Décisions du lot 2
- **Contenu en texte brut** (échappé, paragraphes) : sûr par construction. Le HTML riche arrive avec l'éditeur de blocs (lot 3), validé côté serveur.
- **SEO en colonnes** `meta_title`/`meta_description` de `pages`/`posts` (au lieu d'une table `seo_metadata` séparée) : une jointure de moins ; migrable plus tard.
- **Uploads** : type déterminé par le contenu (`finfo` + `getimagesize`), SVG refusé, nom aléatoire, extension imposée, stockage `public/uploads/<site_id>/`, exécution de scripts interdite par `.htaccess`.
- **Slugs réservés** (`admin`, `terrains`, `actualites`, `assets`, `uploads`, `platform`, `sitemap.xml`, `robots.txt`) ; route `/{slug}` déclarée en dernier.
- **Liens** (menus, redirections) : chemins `/…` ou `http(s)://` uniquement (pas de `javascript:`).
- **Thèmes** : liste blanche de 3 fichiers CSS (variables) ; aucun code fourni par l'utilisateur n'est exécuté.
- Limites connues : redirections 301 seulement pour les chemins sans page correspondante ; sitemap limité à 1000 terrains ; catégories/tags d'articles non implémentés ; menus sans réordonnancement (ordre d'ajout).

## Décisions du lot 4
- **Documents fonciers** : `storage/private/<site_id>/` (hors web root), nom aléatoire, MIME vérifié sur le contenu (PDF/JPEG/PNG, 10 Mo). Servis uniquement par contrôleur, en pièce jointe, avec le type stocké. Côté public : seulement `visibility = public` d'un terrain publié ; l'état « vérifié » n'est affiché qu'après validation explicite par un administrateur (par défaut : « en attente de vérification »).
- **Isolation en base** : `land_images`, `land_documents`, `land_plots`, `project_images`… référencent leurs parents par FK composites `(site_id, id)` ; un test vérifie qu'un lien vers le média d'un autre site est refusé par la base.
- **Localisations** : arbre libre (district → quartier) administrable ; **un seul filtre hiérarchique** (récursif, inclut les descendants) au lieu de six listes séparées.
- **PDF** : FPDF (1 fichier, sans extension système) ; **QR** : chillerlan/php-qrcode (SVG pour l'impression, PNG). Seules dépendances de production du CMS.
- **Logo dans le PDF** : chemin relatif à `public/` validé (`realpath` + préfixe) pour exclure toute lecture hors du répertoire public ; `..` refusé dans les réglages (correction d'une faille du lot 2).
- **Services BTP** : aucun pré-rempli ; créés *inactifs*, invisibles du public jusqu'à activation. Réalisations : aucune pré-remplie.
- **Parcelles** : champ `geometry` réservé au futur plan interactif (non utilisé).
- Limites : carte = lien OpenStreetMap (pas de carte intégrée avant le lot design) ; galeries sans réordonnancement ; pas de photos par parcelle ; la suppression d'un média encore utilisé par un service est refusée par la base.
