-- Lot 3 : éditeur de blocs. draft_blocks = copie de travail ; blocks = version publiée (JSON versionné {"v":1,"blocks":[…]}).
ALTER TABLE pages ADD COLUMN draft_blocks MEDIUMTEXT NULL, ADD COLUMN blocks MEDIUMTEXT NULL;
ALTER TABLE page_revisions ADD COLUMN blocks MEDIUMTEXT NULL;
