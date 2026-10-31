# Conformité au cahier des charges V5

État au dernier lot. ✅ fait et testé · 🟡 partiel (écart indiqué) · ❌ non fait. Rien n'est marqué ✅ sans test ou vérification exécutée.

## Livrables (§25)
| # | Livrable | État | Détail |
|---|---|---|---|
| 1 | CMS multisite PHP/MySQL | ✅ | Résolution par domaine/sous-domaine, isolation `site_id` + clés étrangères composites, tests d'isolation |
| 2 | Administration centrale | 🟡 | Créer, suspendre, réactiver, **supprimer en 2 étapes** (+ purge CLI), domaines (ajout), statistiques, sauvegardes. ❌ modifier un site (nom), retirer un domaine, plans, paramètres globaux, gestion des super administrateurs (CLI `superadmin`), assistant pas-à-pas |
| 3 | Administration Kibarou | ✅ | Tableau de bord, menus du §15 |
| 4 | Gestion des domaines | 🟡 | Ajout et résolution ✅ ; retrait ❌ (service présent, pas d'écran) |
| 5 | Utilisateurs et permissions | 🟡 | 6 rôles, RBAC, écran Utilisateurs (ajout, rôle, retrait, mot de passe) ✅. Matrice de permissions **non éditable** (rôles fixes) |
| 6 | Éditeur visuel par blocs | ✅ | 30 blocs, imbrication, glisser-déposer, brouillon/publication/versions, aperçus. Pages uniquement (pas les articles) |
| 7 | Système de thèmes | 🟡 | 3 thèmes ✅. Personnalisation par site limitée au choix du thème, au logo et aux photos d'accueil ❌ (couleurs, typographies, header/footer, modèles de pages) |
| 8 | Site public Kibarou | 🟡 | Accueil, catalogue, services, réalisations, actualités, contact ✅. ❌ Témoignages (module + section d'accueil ; seul le bloc de l'éditeur existe), page « À propos » (à rédiger par le client, rien d'inventé) |
| 9 | Catalogue foncier | ✅ | Filtres serveur, tri, pagination, statuts. Écart : **un filtre hiérarchique de localisation** au lieu de 6 listes |
| 10 | Fiches terrains | ✅ | Galerie, GPS, documents, similaires, PDF, JSON-LD. Carte = lien OpenStreetMap (le bloc « Carte » de l'éditeur intègre la carte) |
| 11 | Lotissements | ✅ | Parcelles ; plan interactif préparé (champ réservé), non réalisé (prévu « future ») |
| 12 | Module BTP | ✅ | Services créés inactifs, devis |
| 13 | Portfolio | ✅ | Aucune réalisation pré-remplie |
| 14 | CRM commercial | ✅ | Statuts, affectation, notes, historique, visites, export CSV |
| 15 | Génération PDF | ✅ | FPDF |
| 16 | QR codes | ✅ | SVG + PNG |
| 17 | Module SEO | 🟡 | Meta, canonical, Open Graph (sans image), sitemap, robots, JSON-LD (Organisation, Produit), 301 ✅. ❌ table `seo_metadata` (colonnes à la place), `og:image` |
| 18 | Système de sauvegarde | ✅ | Zip base + fichiers, manifeste, cron, restauration validée (anti zip-slip) |
| 19 | Installateur cPanel | ✅ | Assistant web auto-verrouillé + test Apache de bout en bout |
| 20 | Documentation | ✅ | README, INSTALLATION, ARCHITECTURE, DATABASE, SECURITY, DEPLOYMENT_CPANEL, USER_GUIDE, TESTING, DESIGN, CONFORMITE |
| 21 | Tests automatisés | ✅ | 133 PHPUnit, 9 scénarios Playwright, 52 contrôles de déploiement, avec et sans mod_rewrite |

## Points du cahier des charges non réalisés ou partiels
- **Photographies réelles** : aucune incluse (banques d'images bloquées pendant le développement). Les trois scènes d'accueil sont dessinées et remplaçables (Réglages). À fournir par le client.
- **Menus du §15** : *Documents* (liste globale), *Témoignages*, *FAQ* ❌ (documents gérés par terrain ; FAQ et témoignages via les blocs de l'éditeur) ; *Catégories* ✅ ; *Notes commerciales* ✅ (par prospect).
- **Tables du §17** : `categories`/`tags` (articles), `testimonials`, `faqs`, `seo_metadata` ❌.
- **Optimisation d'images** (redimensionnement, WebP) ❌ ; **cache de pages** ❌.
- **Antispam** : sans service externe (champ piège, jeton signé, plafond par IP).
- **Tests de charge, accessibilité automatisée complète, test sur un vrai cPanel** : non faits.
- **PHP 8.3** testé ; 8.2 non exécuté (la contrainte `>=8.2` est déclarée).

## Méthode (§4, §23)
- **Superpowers** : la procédure de brainstorming a été chargée au démarrage ; vous avez demandé de ne pas vous arrêter à la planification, donc l'étape de questions a été écartée. Les phases du §23 ont été suivies par lots, avec tests et débogage systématique (cause avant correctif), mais le skill *writing-plans* n'a pas produit de documents de plan séparés ni de revue formelle de code.
- **Impeccable** : utilisé pour l'administration (lot 6). `PRODUCT.md` rédigé à partir du cahier, sans interview.
- **taste-skill** : utilisé pour le site public (lot 6) ; son inspecteur signale 1 échec (pas de photographies réelles).
- **Playwright** : bibliothèque `playwright` avec `node:test` (pas le skill *playwright-cli*).

## À faire avant la mise en ligne
1. Fournir les photographies et le contenu réel (présentation, « à propos », réalisations, témoignages vérifiés, WhatsApp).
2. Valider les textes provisoires de l'accueil (« Pourquoi nous choisir », domaines d'activité).
3. Définir la politique de confidentialité et la durée de conservation (IP et consentement sont enregistrés avec chaque demande).
4. Déployer sur le cPanel réel, activer le SSL, planifier la sauvegarde **et tester une restauration**.
5. Remplacer les identifiants de développement ; supprimer les données de test `E2E-…` si la base de développement est réutilisée.
