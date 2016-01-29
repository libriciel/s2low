INSERT INTO actes_status (id,name) VALUES(19,'En attente de transmission au SAE');
INSERT INTO actes_status (id,name) VALUES(20,'Erreur lors de l''envoi au SAE');

-- on va prendre les même identifiants même si ca fait des trous dans helios_status...

INSERT INTO helios_status (id,name) VALUES(19,'En attente de transmission au SAE');
INSERT INTO helios_status (id,name) VALUES(20,'Erreur lors de l''envoi au SAE');
