SET CLIENT_ENCODING TO 'LATIN9';

CREATE SEQUENCE authority_groups_id_seq;
CREATE TABLE authority_groups (
	id integer PRIMARY KEY DEFAULT nextval('authority_groups_id_seq'),
	name VARCHAR(128),
	status integer
);

CREATE SEQUENCE authority_group_siren_id_seq;
CREATE TABLE authority_group_siren (
	id integer PRIMARY KEY DEFAULT nextval('authority_group_siren_id_seq'),
	authority_group_id integer,
	siren VARCHAR(10)
);

ALTER TABLE authorities ADD authority_group_id integer DEFAULT NULL;
ALTER TABLE authorities ADD CONSTRAINT authorities_authority_group_id_fk FOREIGN KEY (authority_group_id) REFERENCES authority_groups(id);

ALTER TABLE authority_group_siren ADD CONSTRAINT authority_group_siren_authority_group_id_fk FOREIGN KEY (authority_group_id) REFERENCES authority_groups(id);

ALTER TABLE users ADD authority_group_id integer DEFAULT NULL;
ALTER TABLE users ADD CONSTRAINT users_authority_group_id_fk FOREIGN KEY (authority_group_id) REFERENCES authority_groups(id);

ALTER TABLE authorities ADD broadcast_email VARCHAR(255);
