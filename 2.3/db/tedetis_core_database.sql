SET CLIENT_ENCODING TO 'LATIN9';

CREATE TABLE authority_types (
	id integer PRIMARY KEY,
	parent_type_id integer,
	description VARCHAR(512)
);

CREATE SEQUENCE authorities_id_seq;
CREATE TABLE authorities (
	id integer PRIMARY KEY DEFAULT nextval('authorities_id_seq'),
	authority_type_id integer,
	status integer,
	name VARCHAR(255),
 	email VARCHAR(255),
 	ext_siret VARCHAR (5),
    siren VARCHAR(10),
    agreement VARCHAR(64),
	address VARCHAR(255),
	postal_code integer,
	city VARCHAR(255),
	telephone VARCHAR(25),
	fax VARCHAR(25),
	department char(3),
	district char(1)
);

CREATE SEQUENCE users_id_seq;
CREATE TABLE users (
	id integer PRIMARY KEY DEFAULT nextval('users_id_seq'),
 	email VARCHAR(255),
	subject_dn VARCHAR(512) NOT NULL,
	issuer_dn VARCHAR(512) NOT NULL,
	name VARCHAR(100),
	givenname VARCHAR(100),
	telephone VARCHAR(25),
	role varchar(5) NOT NULL,
	authority_id integer NOT NULL,
	status integer,
    certificate text,
	cert_not_before timestamp with time zone,
	cert_not_after timestamp with time zone,
	cert_serial VARCHAR(32)
);

CREATE SEQUENCE users_perms_id_seq;
CREATE TABLE users_perms (
	id integer PRIMARY KEY DEFAULT nextval('users_perms_id_seq'),
	module_id integer,
	user_id integer,
	perm VARCHAR(10)
);

CREATE SEQUENCE modules_id_seq;
CREATE TABLE modules (
	id integer PRIMARY KEY DEFAULT nextval('modules_id_seq'),
	name VARCHAR(50),
	description VARCHAR(128),
	menu_entry VARCHAR(128),
	status integer not null DEFAULT 1
);


CREATE SEQUENCE authority_departments_id_seq;
CREATE TABLE authority_departments (
	id integer PRIMARY KEY DEFAULT nextval('authority_departments_id_seq'),
    code VARCHAR(3),
    name VARCHAR(128)
);

CREATE SEQUENCE authority_districts_id_seq;
CREATE TABLE authority_districts (
	id integer PRIMARY KEY DEFAULT nextval('authority_districts_id_seq'),
	authority_department_id integer,
    code VARCHAR(1),
    name VARCHAR(128)
);

CREATE SEQUENCE modules_authorities_id_seq;
CREATE TABLE modules_authorities (
	id integer PRIMARY KEY DEFAULT nextval('modules_authorities_id_seq'),
	module_id integer NOT NULL,
	authority_id integer NOT NULL
);

CREATE SEQUENCE modules_params_id_seq;
CREATE TABLE modules_params (
	id integer PRIMARY KEY DEFAULT nextval('modules_params_id_seq'),
	module_id integer NOT NULL,
	name VARCHAR(64),
	value VARCHAR(256),
    description VARCHAR(512)
);

CREATE SEQUENCE logs_id_seq;
CREATE TABLE logs (
	id integer PRIMARY KEY DEFAULT nextval('logs_id_seq'),
	date timestamp with time zone NOT NULL,
	severity integer,
	module varchar(50),
	issuer varchar(30),
	user_id integer,
	visibility varchar(5),
	message text,
	timestamp text
);


-- Constraints
ALTER TABLE authority_types ADD CONSTRAINT authority_types_parent_type_id_fk FOREIGN KEY (parent_type_id) REFERENCES authority_types(id);

ALTER TABLE authorities ADD CONSTRAINT authorities_authority_type_id_fk FOREIGN KEY (authority_type_id) REFERENCES authority_types(id);

ALTER TABLE users ADD CONSTRAINT users_entity_id_fk FOREIGN KEY (authority_id) REFERENCES authorities(id);

ALTER TABLE users_perms ADD CONSTRAINT users_perms_module_id_fk FOREIGN KEY (module_id) REFERENCES modules(id);
ALTER TABLE users_perms ADD CONSTRAINT users_perms_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id);

ALTER TABLE modules_authorities ADD CONSTRAINT modules_authorities_module_id_fk FOREIGN KEY (module_id) REFERENCES modules(id);
ALTER TABLE modules_authorities ADD CONSTRAINT modules_authorities_authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities(id);

ALTER TABLE modules_params ADD CONSTRAINT modules_params_id_fk FOREIGN KEY (module_id) REFERENCES modules(id);

CREATE UNIQUE INDEX modules_name_idx ON modules (name);

-- Initial data

-- Authorities type
INSERT INTO authority_types (id, parent_type_id, description) VALUES (1, null, 'Région');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (11, 1, 'Conseil régional');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (12, 1, 'Établissements publics locaux d\'enseignement');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (13, 1, 'Autres établissements publics');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (14, 1, 'Sociétés d\'économie mixte locales');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (2, null, 'Département');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (21, 2, 'Conseil général');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (22, 2, 'Établissements publics de santé');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (23, 2, 'Établissements publics locaux d\'enseignement');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (24, 2, 'Autres établissements publics');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (25, 2, 'Sociétés d\'économie mixte locales');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (3, null, 'Commune');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (31, 3, 'Commune');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (32, 3, 'Établissements publics de santé');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (33, 3, 'Autres établissements publics');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (34, 3, 'Sociétés d\'économie mixte locales');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (4, null, 'Établissements publics de coopération intercommunale et syndicats');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (41, 4, 'Syndicats de communes et syndicats mixtes « fermés » associant exclusivement des communes, et des EPCI');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (42, 4, 'Syndicats mixtes « ouverts » associant des collectivités territoriales, des groupements de collectivités territoriales et d\'autres personnes morales de droit public');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (43, 4, 'Syndicats d\'agglomération nouvelle');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (44, 4, 'Communautés de communes');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (45, 4, 'Communautés urbaines');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (46, 4, 'Communautés d\'agglomération');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (47, 4, 'Sociétés d\'économie mixte locales');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (5, null, 'Autres');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (51, 5, 'Service départemental d\'incendie et de secours');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (52, 5, 'Entente interdépartementale');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (53, 5, 'Entente interrégionale');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (54, 5, 'Autres sociétés d\'économie mixte locales');
INSERT INTO authority_types (id, parent_type_id, description) VALUES (55, 5, 'Autres');

-- Admin authority
INSERT INTO authorities (id, status, name) VALUES(nextval('authorities_id_seq'), 1, 'Administrateurs');
