-- CMS : pages (avec révisions), articles, médias, menus, redirections 301. SEO en colonnes meta_* de pages/posts.
CREATE TABLE pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  slug VARCHAR(190) NOT NULL,
  title VARCHAR(190) NOT NULL,
  body MEDIUMTEXT NULL,
  meta_title VARCHAR(190) NULL,
  meta_description VARCHAR(300) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_page_slug (site_id, slug),
  UNIQUE KEY uq_page_tenant (site_id, id),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE page_revisions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  page_id INT UNSIGNED NOT NULL,
  title VARCHAR(190) NOT NULL,
  body MEDIUMTEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (site_id, page_id),
  FOREIGN KEY (site_id, page_id) REFERENCES pages(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  slug VARCHAR(190) NOT NULL,
  title VARCHAR(190) NOT NULL,
  body MEDIUMTEXT NULL,
  meta_title VARCHAR(190) NULL,
  meta_description VARCHAR(300) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_post_slug (site_id, slug),
  INDEX idx_post_list (site_id, status, published_at),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  filename VARCHAR(100) NOT NULL,
  original_name VARCHAR(190) NOT NULL,
  mime VARCHAR(50) NOT NULL,
  size INT UNSIGNED NOT NULL,
  alt VARCHAR(190) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_media_file (site_id, filename),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE menus (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  location ENUM('header','footer') NOT NULL,
  UNIQUE KEY uq_menu_loc (site_id, location),
  UNIQUE KEY uq_menu_tenant (site_id, id),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE menu_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  menu_id INT UNSIGNED NOT NULL,
  label VARCHAR(100) NOT NULL,
  url VARCHAR(500) NOT NULL,
  position INT NOT NULL DEFAULT 0,
  INDEX (site_id, menu_id, position),
  FOREIGN KEY (site_id, menu_id) REFERENCES menus(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE redirects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  from_path VARCHAR(500) NOT NULL,
  to_url VARCHAR(500) NOT NULL,
  UNIQUE KEY uq_redirect (site_id, from_path(190)),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;
