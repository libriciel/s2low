ALTER TABLE helios_transactions ADD COLUMN sae_transfer_identifier varchar(256) ;
INSERT INTO helios_status (id,name) VALUES(9,'Envoyé au SAE'); 
INSERT INTO helios_status (id,name) VALUES(10,'Accepter par le SAE');
INSERT INTO helios_status (id,name) VALUES(11,'Refuser par le SAE');
ALTER TABLE helios_transactions ADD COLUMN last_status_id INT;
ALTER TABLE helios_transactions ADD archive_url VARCHAR(1024);