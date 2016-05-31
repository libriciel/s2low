SET CLIENT_ENCODING TO 'LATIN9';


CREATE SEQUENCE helios_transactions_id_seq;
CREATE TABLE helios_transactions (
    id integer DEFAULT nextval('helios_transactions_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    filename character varying(1024) NOT NULL,
    file_size integer,
    siren character varying(128),
    sha1 character(40),
    warning_sent integer,
    url_archivage character(1024),
	submission_date timestamp with time zone,
	xml_nomfic character varying(255),
    acquit_filename character varying(255),
	complete_name character varying(150)
);

CREATE UNIQUE INDEX xml_nomfic_index_unique ON helios_transactions USING btree (xml_nomfic);


CREATE SEQUENCE helios_transactions_workflow_id_seq;
CREATE TABLE helios_transactions_workflow (
	id integer PRIMARY KEY DEFAULT nextval('helios_transactions_workflow_id_seq'),
	transaction_id integer NOT NULL,
    status_id integer NOT NULL,
	date timestamp with time zone NOT NULL,
	message varchar(512) NOT NULL
);

CREATE TABLE helios_status (
	id integer PRIMARY KEY,
	name varchar(64) NOT NULL
);

-- Constraints
ALTER TABLE helios_transactions ADD CONSTRAINT helios_transactions_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id);
ALTER TABLE helios_transactions_workflow ADD CONSTRAINT helios_transactions_workflow_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES helios_transactions(id);


-- Module data
INSERT INTO modules (name, description, menu_entry, status) VALUES ('helios', 'Module Helios', 'Transactions Helios', 1);

-- Initial Data

INSERT INTO helios_status (id, name) VALUES (-1, 'Erreur');
INSERT INTO helios_status (id, name) VALUES (0, 'Annulé');
INSERT INTO helios_status (id, name) VALUES (1, 'Posté');
INSERT INTO helios_status (id, name) VALUES (2, 'En attente de transmission. Fichier valide.');
INSERT INTO helios_status (id, name) VALUES (3, 'Transmis');
INSERT INTO helios_status (id, name) VALUES (4, 'Acquittement reçu');
INSERT INTO helios_status (id, name) VALUES (5, 'Validé. Non utilisé en Helios.');
INSERT INTO helios_status (id, name) VALUES (6, 'Refusé');
INSERT INTO helios_status (id, name) VALUES (7, 'En traitement');


--------------------------------------

CREATE SEQUENCE helios_transmission_windows_id_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1;
CREATE TABLE helios_transmission_windows (
	id integer PRIMARY KEY DEFAULT nextval('helios_transmission_windows_id_seq'),
	rate_limit integer
);


CREATE SEQUENCE helios_transmission_window_hours_id_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1;

CREATE TABLE helios_transmission_window_hours (
	id integer PRIMARY KEY DEFAULT nextval('helios_transmission_window_hours_id_seq'),
	transmission_window_id integer,
	window_begin timestamp with time zone,
	window_end timestamp with time zone,
	consumed integer
);


ALTER TABLE helios_transmission_window_hours ADD CONSTRAINT helios_transmission_window_hours_transmission_window_id_fk FOREIGN KEY (transmission_window_id) REFERENCES helios_transmission_windows(id);


CREATE SEQUENCE helios_retour_id_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1;

CREATE TABLE helios_retour (
	id integer PRIMARY KEY DEFAULT nextval('helios_retour_id_seq'),
	siren character varying(255),
	filename character varying(255),
	date timestamp with time zone,
	status integer
);

