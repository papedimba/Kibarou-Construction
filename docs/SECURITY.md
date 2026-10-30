# Sécurité (état Lot 1)

Implémenté et testé : requêtes préparées PDO ; CSRF sur tout POST ; échappement HTML (`e()`) ; mots de passe `password_hash` (rehash auto) ;
session régénérée au login, cookie HttpOnly/SameSite lié à l'hôte ; limitation de tentatives (5/15 min par compte, 20 par IP) ;
réponse de login uniforme (pas d'énumération de comptes) ; RBAC par site ; protection IDOR (accès par `site_id` + `id`) ;
en-têtes CSP/X-Frame-Options/nosniff ; erreurs centralisées journalisées dans `storage/logs/app.log` ; secrets dans `.env` (ignoré par git).

Non encore implémenté : uploads et contrôle MIME, path traversal des fichiers, documents privés, antispam des formulaires, journal d'activité (table prête), 2FA.
