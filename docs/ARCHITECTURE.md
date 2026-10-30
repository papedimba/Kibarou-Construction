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
3. **Lot 3 (livré)** : éditeur visuel par blocs (30 blocs, registre en liste blanche, JSON versionné, brouillon/publication, versions, aperçus Ordinateur/Tablette/Mobile).
4. **Lot 4 (livré)** : module foncier complet (photos, documents privés, localisations, lotissements/parcelles, fiche PDF, QR codes) + services BTP + réalisations. *Reste du §10.5/§11 : formulaires de demande et de devis → lot 5 (CRM).*
5. **Lot 5 (livré)** : CRM (prospects, notes, historique, affectation, visites), formulaire de demande (informations / visite / rappel / devis / partenariat), constructeur de formulaires, antispam, notifications e-mail, export CSV.
6. **Lot 6 (livré)** : design — site public « split stage » (taste-skill), administration « Operate » (Impeccable). Voir `docs/DESIGN.md`. *Reste : photographies réelles, SEO/sitemap déjà faits au lot 2.*
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

## Décisions du lot 5
- **Un seul formulaire de demande** (`/contact`) dont le contexte vient de slugs résolus *dans le site courant* (`?terrain=…`, `?service=…`) : un terrain d'un autre site, non publié, ou un service inactif est refusé. Le type de demande autorisé dépend du contexte.
- **Antispam sans service externe** : champ piège, jeton horodaté signé HMAC (`APP_KEY`) valable 3 s à 6 h, plafond de 5 demandes/heure/IP (prospects + formulaires), CSRF. Un test de mutation a confirmé que les tests échouent si ces protections sont retirées.
- **Historique** : toute modification (statut, affectation, note, visite) passe par `LeadService` et laisse une trace ; l'affectation n'est possible que vers un membre du même site.
- **Intégrité commerciale** : un terrain ou un service lié à des prospects ne peut pas être supprimé (FK) — message explicite, suggestion « Indisponible » / désactiver. Les fichiers ne sont supprimés qu'après la suppression en base réussie.
- **Export CSV** : UTF-8 avec BOM, séparateur `;`, filtres appliqués, cellules commençant par `= + - @` neutralisées contre l'injection de formules (limite 5000 lignes).
- **Notifications** : `MAIL_DRIVER=log` (dev/tests, `storage/logs/mail.log`) ou `mail()` PHP (cPanel). Un échec d'envoi ne perd jamais la demande (déjà enregistrée). En-têtes protégés contre l'injection CRLF.
- **Constructeur de formulaires** : un champ par ligne (`Libellé | type | obligatoire | options`), validation serveur stricte, réponses stockées par libellé. Interface visuelle prévue au lot design.
- **Données personnelles** : adresse IP et consentement horodaté conservés avec chaque demande (antispam / preuve). Politique de conservation et page de confidentialité à définir avec le client.
- Limites : pas de réservation en ligne ni paiement (hors périmètre V1) ; pas de relance automatique ; notifications seulement vers l'e-mail public du site ; prospects non dédoublonnés.

## Décisions du lot 3
- **Le serveur fait autorité.** Le navigateur envoie un arbre JSON ; `BlockSanitizer` ne garde que les types du `BlockRegistry`, les propriétés déclarées, des valeurs coercées par type de champ (listes bornées, nombres bornés, `select` dans ses options, URL `/chemin`, `http(s)`, `mailto`, `tel` uniquement), des images appartenant au site courant, 4 niveaux d'imbrication et 200 blocs max. Aucune couleur, classe, style ou HTML libre n'est acceptable → rien d'exécutable ne peut être stocké.
- **Rendu par gabarits fixes** (`app/Views/blocks/<type>.php`) : chaque valeur est échappée ; aucun style en ligne (CSP) ; les vidéos sont reconstruites à partir d'un identifiant extrait (YouTube/Vimeo) ou d'un `.mp4` https, jamais d'une URL brute ; la CSP autorise uniquement `youtube-nocookie`, `player.vimeo.com` et `openstreetmap.org` en `frame-src`.
- **JSON versionné** `{"v":1,"blocks":[…]}` (`BlockStorage`) : une version inconnue ou un JSON corrompu donne une page vide, qui retombe sur le texte de la page — jamais d'erreur 500.
- **Brouillon ≠ publié** : `draft_blocks` (enregistrer) / `blocks` (publier). Publier archive la version précédente dans `page_revisions` ; restaurer recopie dans le *brouillon* seulement.
- **Aucune perte silencieuse** : une charge utile absente ou illisible est refusée (HTTP 400) au lieu d'être interprétée comme « page vide ».
- **Aperçus fidèles** : le CSS des blocs utilise des *requêtes de conteneur* (`@container`), donc l'aperçu Tablette/Mobile de l'éditeur se comporte comme le vrai site à cette largeur. Le canvas affiche le **rendu public réel** (même gabarits), pas une imitation.
- **Blocs de données** (catalogue, terrain vedette, galeries, services, réalisations) : lus via les repositories du site courant ; un terrain/projet d'un autre site ou non publié n'apparaît jamais (test dédié).
- **Témoignages** : seuls ceux cochés « vérifié » sont publiés ; l'éditeur signale ceux qui sont masqués.
- **Icônes Lucide** (licence ISC, `app/Blocks/icons/`) : 40 icônes locales inlinées côté serveur (aucun CDN, compatible CSP).
- **Page d'accueil** : réglage `home_page` (identifiant d'une page publiée avec blocs) ; sinon accueil par défaut.
- Limites : seules les **pages** utilisent l'éditeur (pas les articles) ; le glisser-déposer a des boutons ↑/↓ de secours mais les blocs ne se déplacent pas hors de leur conteneur avec ↑/↓ (utiliser le glisser-déposer) ; choix d'image parmi les médias déjà téléversés (pas de téléversement dans l'éditeur) ; pas d'annuler/rétablir ; pas d'édition simultanée (dernier enregistrement gagnant) ; le rendu public n'est pas mis en cache ; hero et sections ne sont pas pleine largeur tant que le design (lot 6) n'est pas fait.

## Décisions du lot 6
- **Deux feuilles de style séparées** : `site.css` (public) et `admin.css` (administration, connexion, plateforme) ; `blocks.css` est partagé (blocs et éditeur) et utilise des requêtes de conteneur. Le layout choisit selon `$public`.
- **Polices hébergées localement** (Literata, IBM Plex Sans) : la CSP `style-src 'self'` interdit Google Fonts. Licences OFL dans `public/assets/fonts/`.
- **Aucun script ni style en ligne** : le drapeau « JS actif » est remplacé par `@media (scripting: enabled)` ; la CSP a d'ailleurs bloqué ma première version en ligne, ce qui a prouvé qu'elle fonctionne.
- **Navigation par défaut** construite d'après ce que le site possède (« À propos » seulement si la page existe).
- `Page::render` fournit aux vues les permissions de l'utilisateur sur le site courant (`Rbac::permissions`) pour masquer les liens inaccessibles ; le serveur reste l'autorité (403).
- `fcfa()` utilise l'espace insécable classique : la police n'a pas de glyphe pour l'espace fine insécable.

## Décisions du lot 7
- **Installateur web auto-verrouillé** plutôt qu'un script CLI seul : sur cPanel, beaucoup n'ont pas de shell. Garde-fou : refus si `.env` ou verrou présents ; suppression de `install.php`.
- **Sauvegarde sans `mysqldump`** : export SQL en PHP (une instruction par ligne, valeurs échappées par `PDO::quote`) + zip ; restauration en ligne de commande uniquement (opération destructive, jamais exposée au web).
- **Deux `.htaccess`** : `public/` (cas recommandé, domaine pointé sur `public/`) et racine (cas `public_html` imposé). Le test Apache a trouvé un défaut : ma règle « seuls `index.php`/`install.php` sont des scripts publics » comparait l'URL d'origine et se bloquait elle-même après la réécriture interne (403 partout) ; elle compare désormais le chemin de la cible.
- **Suppression de site en deux temps** (statut `deleted` puis purge CLI) ; la purge supprime explicitement les lignes par `site_id` car les clés étrangères composites interdisent un ordre de cascade simple.
- **Journal d'activité par middleware** : un seul point de code, aucune modification des contrôleurs, aucun contenu stocké.
- **Archive de livraison** : `bin/build-release.sh` (vendor de production inclus, car un hébergement mutualisé n'a souvent pas Composer).
