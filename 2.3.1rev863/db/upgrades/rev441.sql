INSERT INTO actes_status (id,name) VALUES(18,'En attente d\'être signée');
ALTER TABLE actes_included_files ADD COLUMN sha1 varchar(256) DEFAULT '';
