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
2. Lot 2 : CMS (pages, révisions, menus, médias, SEO, paramètres, thèmes).
3. Lot 3 : éditeur de blocs (BlockRegistry en liste blanche, JSON versionné).
4. Lot 4 : module foncier complet (images, documents privés, localisations, lotissements/parcelles, PDF, QR) + BTP + réalisations.
5. Lot 5 : CRM, formulaires, antispam, export CSV.
6. Lot 6 : design (taste-skill / Impeccable), site Kibarou, SEO/sitemap.
7. Lot 7 : installateur cPanel, sauvegardes, documentation de déploiement, campagne E2E complète.

## Hors périmètre actuel (non fait)
Tout ce qui figure aux lots 2 à 7. Le logo officiel est dans `public/assets/images/brand/` mais pas encore intégré aux gabarits.
