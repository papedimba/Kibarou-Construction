INSERT IGNORE INTO permissions (slug) VALUES ('site.settings'),('users.manage'),('land.view'),('land.manage'),('lead.view'),('lead.manage'),('btp.manage'),('content.manage');
INSERT IGNORE INTO roles (slug, name) VALUES ('site_admin','Administrateur de site'),('land_manager','Responsable foncier'),('sales_manager','Responsable commercial'),('btp_manager','Responsable BTP'),('editor','Éditeur');
INSERT IGNORE INTO role_permissions SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.slug='site_admin';
INSERT IGNORE INTO role_permissions SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('land.view','land.manage') WHERE r.slug='land_manager';
INSERT IGNORE INTO role_permissions SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('lead.view','lead.manage','land.view') WHERE r.slug='sales_manager';
INSERT IGNORE INTO role_permissions SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('btp.manage') WHERE r.slug='btp_manager';
INSERT IGNORE INTO role_permissions SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('content.manage') WHERE r.slug='editor';
