-- Lot 5 : CRM (prospects, notes, historique, visites) et formulaires configurables.
ALTER TABLE construction_services ADD UNIQUE KEY uq_service_tenant (site_id, id);

CREATE TABLE leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  reference VARCHAR(20) NOT NULL,
  type ENUM('info','visit','callback','quote','partnership','other') NOT NULL DEFAULT 'info',
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(190) NULL,
  subject VARCHAR(190) NULL,
  message TEXT NULL,
  preferred_date DATE NULL,
  land_id INT UNSIGNED NULL,
  service_id INT UNSIGNED NULL,
  status ENUM('new','to_contact','contacted','visit_scheduled','negotiating','converted','dropped') NOT NULL DEFAULT 'new',
  assigned_to INT UNSIGNED NULL,
  consent_at TIMESTAMP NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_lead_ref (site_id, reference),
  UNIQUE KEY uq_lead_tenant (site_id, id),
  INDEX idx_lead_list (site_id, status, created_at),
  INDEX idx_lead_ip (site_id, ip, created_at),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
  FOREIGN KEY (site_id, land_id) REFERENCES land_properties(site_id, id),
  FOREIGN KEY (site_id, service_id) REFERENCES construction_services(site_id, id),
  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE lead_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  lead_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (site_id, lead_id),
  FOREIGN KEY (site_id, lead_id) REFERENCES leads(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE lead_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  lead_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  action VARCHAR(40) NOT NULL,
  detail VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (site_id, lead_id),
  FOREIGN KEY (site_id, lead_id) REFERENCES leads(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  lead_id INT UNSIGNED NOT NULL,
  scheduled_at DATETIME NOT NULL,
  note VARCHAR(300) NULL,
  status ENUM('planned','done','cancelled') NOT NULL DEFAULT 'planned',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (site_id, scheduled_at),
  FOREIGN KEY (site_id, lead_id) REFERENCES leads(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE forms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  fields TEXT NOT NULL,
  notify_email VARCHAR(190) NULL,
  success_message VARCHAR(300) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_form_slug (site_id, slug),
  UNIQUE KEY uq_form_tenant (site_id, id),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE form_submissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id INT UNSIGNED NOT NULL,
  form_id INT UNSIGNED NOT NULL,
  data TEXT NOT NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (site_id, form_id, created_at),
  INDEX idx_sub_ip (site_id, ip, created_at),
  FOREIGN KEY (site_id, form_id) REFERENCES forms(site_id, id) ON DELETE CASCADE
) ENGINE=InnoDB;
