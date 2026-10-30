-- Lot 4 : foncier étendu (images, documents privés, lotissements, parcelles), services BTP, réalisations.
ALTER TABLE media ADD UNIQUE KEY uq_media_tenant (site_id, id);

ALTER TABLE land_properties
  ADD COLUMN latitude DECIMAL(9,6) NULL,
  ADD COLUMN longitude DECIMAL(9,6) NULL,
  ADD COLUMN features TEXT NULL,
  ADD COLUMN land_info TEXT NULL;

CREATE TABLE land_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  land_id INT UNSIGNED NOT NULL,
  media_id INT UNSIGNED NOT NULL,
  position INT NOT NULL DEFAULT 0,
  INDEX (site_id, land_id, position),
  FOREIGN KEY (site_id, land_id) REFERENCES land_properties(site_id, id) ON DELETE CASCADE,
  FOREIGN KEY (site_id, media_id) REFERENCES media(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE land_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  land_id INT UNSIGNED NOT NULL,
  type ENUM('acd','titre_foncier','attestation','lotissement','plan','autre') NOT NULL,
  doc_date DATE NULL,
  verification ENUM('unverified','verified') NOT NULL DEFAULT 'unverified',
  visibility ENUM('private','public') NOT NULL DEFAULT 'private',
  filename VARCHAR(100) NOT NULL,
  original_name VARCHAR(190) NOT NULL,
  mime VARCHAR(50) NOT NULL,
  size INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (site_id, land_id),
  FOREIGN KEY (site_id, land_id) REFERENCES land_properties(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE land_developments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  description TEXT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_dev_slug (site_id, slug),
  UNIQUE KEY uq_dev_tenant (site_id, id),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
  FOREIGN KEY (site_id, location_id) REFERENCES locations(site_id, id)
) ENGINE=InnoDB;

CREATE TABLE land_plots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  development_id INT UNSIGNED NOT NULL,
  reference VARCHAR(40) NOT NULL,
  number VARCHAR(20) NOT NULL,
  area_m2 INT UNSIGNED NOT NULL,
  price_xof BIGINT UNSIGNED NOT NULL,
  status ENUM('available','reserving','reserved','sold','unavailable') NOT NULL DEFAULT 'available',
  location_note VARCHAR(190) NULL,
  geometry TEXT NULL, -- réservé : futur plan interactif (GeoJSON/SVG), non utilisé
  UNIQUE KEY uq_plot_ref (site_id, reference),
  UNIQUE KEY uq_plot_num (site_id, development_id, number),
  FOREIGN KEY (site_id, development_id) REFERENCES land_developments(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE construction_services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  media_id INT UNSIGNED NULL,
  category VARCHAR(40) NOT NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_service_slug (site_id, slug),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
  FOREIGN KEY (site_id, media_id) REFERENCES media(site_id, id)
) ENGINE=InnoDB;

CREATE TABLE projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  category VARCHAR(40) NOT NULL,
  description TEXT NULL,
  location_text VARCHAR(190) NULL,
  completed_on DATE NULL,
  status ENUM('planned','in_progress','completed') NOT NULL DEFAULT 'completed',
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_project_slug (site_id, slug),
  UNIQUE KEY uq_project_tenant (site_id, id),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE project_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  project_id INT UNSIGNED NOT NULL,
  media_id INT UNSIGNED NOT NULL,
  position INT NOT NULL DEFAULT 0,
  INDEX (site_id, project_id, position),
  FOREIGN KEY (site_id, project_id) REFERENCES projects(site_id, id) ON DELETE CASCADE,
  FOREIGN KEY (site_id, media_id) REFERENCES media(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;
