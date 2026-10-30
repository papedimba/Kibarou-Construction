# Sécurité (état Lot 1)

Implémenté et testé : requêtes préparées PDO ; CSRF sur tout POST ; échappement HTML (`e()`) ; mots de passe `password_hash` (rehash auto) ;
session régénérée au login, cookie HttpOnly/SameSite lié à l'hôte ; limitation de tentatives (5/15 min par compte, 20 par IP) ;
réponse de login uniforme (pas d'énumération de comptes) ; RBAC par site ; protection IDOR (accès par `site_id` + `id`) ;
en-têtes CSP/X-Frame-Options/nosniff ; erreurs centralisées journalisées dans `storage/logs/app.log` ; secrets dans `.env` (ignoré par git).

Lot 2 : uploads contrôlés par contenu (MIME réel, SVG refusé, 5 Mo, nom aléatoire, exécution interdite dans `public/uploads`), suppression de fichier par `basename`, contenu échappé (pas de HTML brut), liens `javascript:` refusés, thèmes en liste blanche.

Lot 4 : documents fonciers hors web root, servis par contrôleur (IDOR testé), vérification jamais présumée, `realpath` pour le logo du PDF, QR/PDF scopés au site, localisations/catégories d'un autre site refusées, services désactivés par défaut.

Lot 5 : antispam à 3 couches (champ piège, jeton signé, plafond par IP) + CSRF, validation serveur des demandes et des formulaires, isolation des prospects testée (lecture, statut, note, visite, suppression, export), affectation limitée aux membres du site, export CSV anti-injection de formules, e-mails protégés contre l'injection d'en-têtes. `APP_KEY` obligatoire en production.

Lot 3 : éditeur de blocs — liste blanche des types et des champs, URL restreintes (pas de `javascript:`/`data:`/`//`), ids d'images vérifiés par site, profondeur/nombre bornés, échappement systématique, aucun style/script en ligne, embeds limités par CSP, JSON corrompu inoffensif, endpoints JSON soumis au CSRF (`_csrf` dans le corps) et au RBAC `content.manage`, isolation des pages (404 inter-sites), tests de mutation sur URL et images.

Non encore implémenté : documents privés, journal d'activité global (table prête), 2FA.
