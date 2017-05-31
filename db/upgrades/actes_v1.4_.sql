SET CLIENT_ENCODING TO 'LATIN9';


CREATE TABLE actes_messages_status (
    id integer PRIMARY KEY not null,
    nom character(64)
);

INSERT INTO actes_messages_status (id, nom) VALUES (1, 'Posté la réponse echoué');
INSERT INTO actes_messages_status (id, nom) VALUES (2, 'Echèc de la transmission');
INSERT INTO actes_messages_status (id, nom) VALUES (3, 'En attente de la transmission');
INSERT INTO actes_messages_status (id, nom) VALUES (4, 'Transmis');

INSERT INTO actes_messages_status (id, nom) VALUES (20, 'un courrier simple reçu');
INSERT INTO actes_messages_status (id, nom) VALUES (21, 'Posté la réponse de courrier simple');
--
INSERT INTO actes_messages_status (id, nom) VALUES (30, 'Réception de la demande des pièces complémentaire ');
INSERT INTO actes_messages_status (id, nom) VALUES (31, 'AR demande pièces complémentaires');
INSERT INTO actes_messages_status (id, nom) VALUES (32, 'Refus de pièce complémentaire posté');
INSERT INTO actes_messages_status (id, nom) VALUES (33, 'Pièce complémentaire postés');
INSERT INTO actes_messages_status (id, nom) VALUES (36, 'AR pièce complémentaire ou refus');
--
INSERT INTO actes_messages_status (id, nom) VALUES (40, 'Réception du lettre d''observation');
INSERT INTO actes_messages_status (id, nom) VALUES (41, 'Ar de lettre d''observation');
INSERT INTO actes_messages_status (id, nom) VALUES (42, 'La réponse de lettre d''observations');
INSERT INTO actes_messages_status (id, nom) VALUES (43, 'Réfus de lettre d''observation');
INSERT INTO actes_messages_status (id, nom) VALUES (46, 'AR lettre d''observation');
--
INSERT INTO actes_messages_status (id, nom) VALUES (50, 'Réception d''info déféré au TA');





CREATE SEQUENCE actes_messages_id_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1;
CREATE TABLE actes_messages (
    id integer PRIMARY KEY DEFAULT nextval('actes_messages_id_seq'::regclass) NOT NULL,
    actes_transactions_id integer,
    messages_type integer,
    actes_messages_status_id integer,
	received_date timestamp with time zone,
   	FOREIGN KEY (actes_transactions_id) references actes_transactions(id),
	FOREIGN KEY (actes_messages_status_id) references actes_messages_status(id)
);

CREATE SEQUENCE actes_messages_reponses_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1;
CREATE TABLE actes_messages_reponses (
    id integer PRIMARY KEY DEFAULT nextval('actes_messages_reponses_seq'::regclass) NOT NULL,
    actes_messages_id integer,
    dir_path char varying(4096),
	xml_filename char varying(512),
	filename char varying(512),
    agreement boolean,
    file_size integer,
    warning_sent boolean DEFAULT false,
	first_sent_time timestamp with time zone,
    actual_sent_time timestamp with time zone,
   	FOREIGN KEY (actes_messages_id) references actes_messages(id)
);

CREATE SEQUENCE actes_messages_workflow_id_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1;

CREATE TABLE actes_messages_workflow (
    id integer PRIMARY KEY DEFAULT nextval('actes_messages_workflow_id_seq'::regclass) NOT NULL,
    actes_messages_id integer,
    actes_messages_status_id integer,
    action_time timestamp with time zone,
    message char varying (1024),
   	FOREIGN KEY (actes_messages_id) references actes_messages(id),
	FOREIGN KEY (actes_messages_status_id) references actes_messages_status(id)
);

