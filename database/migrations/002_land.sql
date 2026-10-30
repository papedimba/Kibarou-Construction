-- Données métier foncières. Chaque table porte site_id ; les clés étrangères composites
-- (site_id, parent_id) interdisent en base toute référence entre deux sites.
CREATE TABLE land_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_cat_slug (site_id, slug),
  UNIQUE KEY uq_cat_tenant (site_id, id),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE locations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL,
  type ENUM('district','region','departement','ville','commune','quartier') NOT NULL,
  name VARCHAR(150) NOT NULL,
  UNIQUE KEY uq_loc_tenant (site_id, id),
  INDEX (site_id, type),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
  FOREIGN KEY (site_id, parent_id) REFERENCES locations(site_id, id)
) ENGINE=InnoDB;

CREATE TABLE land_properties (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NULL,
  location_id INT UNSIGNED NULL,
  reference VARCHAR(40) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT NULL,
  area_m2 INT UNSIGNED NOT NULL,
  price_xof BIGINT UNSIGNED NOT NULL,
  status ENUM('available','reserving','reserved','sold','unavailable') NOT NULL DEFAULT 'available',
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_land_ref (site_id, reference),
  UNIQUE KEY uq_land_slug (site_id, slug),
  UNIQUE KEY uq_land_tenant (site_id, id),
  INDEX idx_land_list (site_id, is_published, status, price_xof),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
  FOREIGN KEY (site_id, category_id) REFERENCES land_categories(site_id, id),
  FOREIGN KEY (site_id, location_id) REFERENCES locations(site_id, id)
) ENGINE=InnoDB;
