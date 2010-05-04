ALTER TABLE users ADD login character varying(128) DEFAULT NULL;
ALTER TABLE users ADD password character varying(128) DEFAULT NULL;

CREATE UNIQUE INDEX users_login ON users USING btree (login);

ALTER TABLE mail_annuaire ADD CONSTRAINT mail_annuaire_mail_adresse UNIQUE(authority_id, mail_address);

CREATE SEQUENCE mail_groupe_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE mail_groupe (
    id integer PRIMARY KEY DEFAULT nextval('mail_groupe_id_seq'::regclass) NOT NULL,
    authority_id integer,
    name character varying(128) NOT NULL
);


ALTER TABLE mail_groupe ADD CONSTRAINT mail_groupe_unique UNIQUE(authority_id, name);

CREATE TABLE mail_user_groupe (
    id_user integer,
    id_groupe integer
);

ALTER TABLE mail_user_groupe ADD FOREIGN KEY (id_user) REFERENCES mail_annuaire(id) ON DELETE CASCADE;
ALTER TABLE mail_user_groupe ADD FOREIGN KEY (id_groupe) REFERENCES mail_groupe(id) ON DELETE CASCADE;

ALTER TABLE mail_user_groupe ADD CONSTRAINT mail_user_groupe_unique UNIQUE(id_user,id_groupe);

-- v1.1-R3
ALTER TABLE mail_annuaire  ALTER COLUMN description TYPE character varying(256);
ALTER TABLE mail_annuaire  ALTER COLUMN mail_address TYPE character varying(50);


-- v1.1-R4

CREATE SEQUENCE service_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE service_user (
	id integer PRIMARY KEY DEFAULT nextval('service_user_id_seq'::regclass) NOT NULL,
	authority_id integer,
    name character varying(128) NOT NULL,
    parent_id integer 
);

ALTER TABLE service_user ADD FOREIGN KEY (parent_id) REFERENCES service_user(id) ON DELETE CASCADE;

CREATE TABLE service_user_content (
    id_service integer,
    id_user integer
);

ALTER TABLE service_user_content ADD FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE service_user_content ADD FOREIGN KEY (id_service) REFERENCES service_user(id) ON DELETE CASCADE;

ALTER TABLE service_user_content ADD CONSTRAINT service_user_content_unique UNIQUE(id_user,id_service);

ALTER TABLE authorities ADD  email_mail_securise character varying(256);


-- v1.1-R5
ALTER TABLE mail_message_emis ADD  ack_date timestamp with time zone;
ALTER TABLE actes_transactions ADD  auto_broadcasted BOOLEAN DEFAULT false;
UPDATE actes_transactions SET auto_broadcasted=true;
