# Base de données

Migrations versionnées : `database/migrations/*.sql`, appliquées par `php bin/console.php migrate` (table `migrations`).

**Lot 1** : `sites`, `domains` (`host` UNIQUE), `users`, `roles`, `permissions`, `role_permissions`, `site_members` (UNIQUE site/user/rôle),
`settings` (UNIQUE site/clé), `activity_logs`, `login_attempts`, `land_categories`, `locations`, `land_properties`.

**Isolation en base** : chaque table métier porte `site_id` et un index `UNIQUE (site_id, id)` ; les références
(`land_properties.category_id`, `locations.parent_id`…) sont des FK composites `(site_id, x_id)`, donc impossibles entre sites.
Les unicités métier sont par site (`UNIQUE (site_id, reference)`, `(site_id, slug)`).

Tables prévues aux lots suivants : voir §17 du cahier des charges.

**Lot 2** (`004_cms.sql`) : `pages`, `page_revisions` (FK composite vers `pages`), `posts`, `media`, `menus`, `menu_items` (FK composite), `redirects`. Toutes isolées par `site_id`.
