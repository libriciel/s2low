
SET CLIENT_ENCODING TO 'LATIN9';


--
-- Name: mail_annuaire_id_seq; Type: SEQUENCE; Schema: public; Owner: tedetis
--

CREATE SEQUENCE mail_annuaire_id_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1;


--
-- Name: mail_annuaire; Type: TABLE; Schema: public; Owner: tedetis; Tablespace: 
--
CREATE TABLE mail_annuaire (
    id integer PRIMARY KEY DEFAULT nextval('mail_annuaire_id_seq'::regclass) NOT NULL,
    authority_id integer NOT NULL,
    mail_address character(50) NOT NULL,
    description character(256)
);




--
-- Name: mail_errors; Type: TABLE; Schema: public; Owner: tedetis; Tablespace: 
--


CREATE SEQUENCE mail_errors_id_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1;



CREATE TABLE mail_errors (
    id integer PRIMARY KEY DEFAULT nextval('mail_errors_id_seq'::regclass) NOT NULL,
    mail_message_emis_id character(128),
    date_registered timestamp with time zone,
    message_retour text
);



--
-- Name: COLUMN mail_errors.date_registered; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_errors.date_registered IS 'timestamp au moment de la réception de l''erreur';


--
-- Name: COLUMN mail_errors.message_retour; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_errors.message_retour IS 'contenu du message erreur retourné';

--
-- Name: mail_included_file_id_seq; Type: SEQUENCE; Schema: public; Owner: tedetis
--

CREATE SEQUENCE mail_included_file_id_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1
    CYCLE;


--
-- Name: mail_included_file; Type: TABLE; Schema: public; Owner: tedetis; Tablespace: 
--

CREATE TABLE mail_included_file (
    id integer PRIMARY KEY DEFAULT nextval('mail_included_file_id_seq'::regclass) NOT NULL,
    mail_transaction_id integer NOT NULL,
    filename character varying(512) NOT NULL,
    filetype character varying(64) NOT NULL,
    filesize integer NOT NULL
);


--
-- Name: mail_included_file_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: tedetis
--



--
-- Name: mail_message_emis; Type: TABLE; Schema: public; Owner: tedetis; Tablespace: 
--

CREATE TABLE mail_message_emis (
    id character(128) PRIMARY KEY NOT NULL,
    mail_transaction_id integer NOT NULL,
    email character varying(256) NOT NULL,
    type_envoi character varying(64) NOT NULL,
    ack boolean DEFAULT false NOT NULL
);


--
-- Name: COLUMN mail_message_emis.id; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_message_emis.id IS 'md5 code.';


--
-- Name: COLUMN mail_message_emis.email; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_message_emis.email IS 'email daress du destinataire (to)';


--
-- Name: COLUMN mail_message_emis.type_envoi; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_message_emis.type_envoi IS 'type: TO, CC(Carbon Copy),  BCC(Blind Carbon Copy )';


--
-- Name: COLUMN mail_message_emis.ack; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_message_emis.ack IS 'Le destinataire a cliqué sur le lien retour.';


--
-- Name: mail_transaction_id_seq; Type: SEQUENCE; Schema: public; Owner: tedetis
--

CREATE SEQUENCE mail_transaction_id_seq
    INCREMENT BY 1
    MAXVALUE 999999999999999
    NO MINVALUE
    CACHE 1;

--
-- Name: mail_transaction; Type: TABLE; Schema: public; Owner: tedetis; Tablespace: 
--

CREATE TABLE mail_transaction (
    id integer PRIMARY KEY DEFAULT nextval('mail_transaction_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    objet character varying(1024) NOT NULL,
    message character varying(2048) NOT NULL,
    fn_download character varying(512),
    status character varying(40) NOT NULL,
    date_envoi timestamp with time zone,
    "password" character varying(64)
);




--
-- Name: COLUMN mail_transaction.objet; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_transaction.objet IS 'titre du message';


--
-- Name: COLUMN mail_transaction.message; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_transaction.message IS 'corps du message(html)';


--
-- Name: COLUMN mail_transaction.fn_download; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_transaction.fn_download IS 'MD5(id_trans) = nom du ficheir dans répertoire mail/uploads/';


--
-- Name: COLUMN mail_transaction.status; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_transaction.status IS '3 valeur:aucun,confirm,confirm partielle
valeur pardefaut:aucun';


--
-- Name: COLUMN mail_transaction."password"; Type: COMMENT; Schema: public; Owner: tedetis
--

COMMENT ON COLUMN mail_transaction."password" IS 'Mot de passe pour récupérer fichiers';

GRANT ALL PRIVILEGES ON TABLE mail_annuaire TO tedetis;
GRANT ALL PRIVILEGES ON mail_annuaire_id_seq TO tedetis;
GRANT ALL PRIVILEGES ON mail_transaction TO tedetis;
GRANT ALL PRIVILEGES ON mail_transaction_id_seq TO tedetis;
GRANT ALL PRIVILEGES ON  mail_message_emis TO tedetis;
GRANT ALL PRIVILEGES ON  mail_included_file TO tedetis;
GRANT ALL PRIVILEGES ON  mail_errors TO tedetis;
GRANT ALL PRIVILEGES ON  mail_included_file_id_seq TO tedetis;
INSERT INTO modules (name, description, menu_entry, status)
    VALUES ('mail', 'Module Mail', 'Transactions Mail', 1);
