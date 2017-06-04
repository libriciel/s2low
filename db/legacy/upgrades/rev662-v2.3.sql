ALTER TABLE helios_retour ADD COLUMN authority_id INT;

UPDATE helios_retour SET authority_id=authorities.id FROM authorities WHERE authorities.siren=helios_retour.siren;

ALTER TABLE helios_retour ADD CONSTRAINT 
helios_retour_authority_id FOREIGN KEY (authority_id) REFERENCES authorities(id);


ALTER TABLE helios_retour ADD COLUMN siret CHAR(14);

-- La requête de création des helios_retour n'utilisait pas la bonne séquence !
SELECT setval('helios_retour_id_seq', (SELECT max(id) from helios_retour));



CREATE SEQUENCE authority_siret_id_seq;
CREATE TABLE authority_siret (
	id integer PRIMARY KEY DEFAULT nextval('authority_siret_id_seq'),
	authority_id integer,
	siret CHAR(14),
	date timestamp with time zone
);
ALTER TABLE authority_siret ADD CONSTRAINT authority_siret_authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities(id);

ALTER TABLE helios_transactions ADD COLUMN signature_technique boolean default false NOT NULL;

ALTER TABLE logs ADD COLUMN authority_id INTEGER;
UPDATE logs SET authority_id = users.authority_id FROM users WHERE logs.user_id=users.id;
ALTER TABLE logs ADD CONSTRAINT logs_authority_id FOREIGN KEY (authority_id) REFERENCES authorities(id);
CREATE INDEX authority_index ON logs USING btree (authority_id,id);


ALTER TABLE logs ADD COLUMN authority_group_id INTEGER;
UPDATE logs SET authority_group_id = authorities.authority_group_id FROM authorities WHERE logs.authority_id=authorities.id;
ALTER TABLE logs ADD CONSTRAINT logs_authority_group_id FOREIGN KEY (authority_group_id) REFERENCES authority_groups(id);
CREATE INDEX authority_group_index ON logs USING btree (authority_group_id,id);
