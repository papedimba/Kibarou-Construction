# Base de données

Migrations versionnées : `database/migrations/*.sql`, appliquées par `php bin/console.php migrate` (table `migrations`).

**Lot 1** : `sites`, `domains` (`host` UNIQUE), `users`, `roles`, `permissions`, `role_permissions`, `site_members` (UNIQUE site/user/rôle),
`settings` (UNIQUE site/clé), `activity_logs`, `login_attempts`, `land_categories`, `locations`, `land_properties`.

**Isolation en base** : chaque table métier porte `site_id` et un index `UNIQUE (site_id, id)` ; les références
(`land_properties.category_id`, `locations.parent_id`…) sont des FK composites `(site_id, x_id)`, donc impossibles entre sites.
Les unicités métier sont par site (`UNIQUE (site_id, reference)`, `(site_id, slug)`).

Tables prévues aux lots suivants : voir §17 du cahier des charges.

**Lot 2** (`004_cms.sql`) : `pages`, `page_revisions` (FK composite vers `pages`), `posts`, `media`, `menus`, `menu_items` (FK composite), `redirects`. Toutes isolées par `site_id`.

**Lot 4** (`005_land_btp.sql`) : `land_images`, `land_documents`, `land_developments`, `land_plots`, `construction_services`, `projects`, `project_images` ; colonnes GPS/caractéristiques/infos foncières sur `land_properties`. FK composites `(site_id, …)` partout.

**Lot 5** (`006_crm.sql`) : `leads` (FK composites vers terrain/service, `assigned_to` → users), `lead_notes`, `lead_history`, `appointments`, `forms`, `form_submissions`.

**Lot 3** (`007_blocks.sql`) : `pages.draft_blocks`, `pages.blocks` (JSON versionné), `page_revisions.blocks`.
