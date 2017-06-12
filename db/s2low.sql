CREATE SEQUENCE actes_batch_files_id_seq;
CREATE SEQUENCE actes_batches_id_seq;
CREATE SEQUENCE actes_classification_codes_id_seq;
CREATE SEQUENCE actes_classification_requests_id_seq;
CREATE SEQUENCE actes_envelope_serials_id_seq;
CREATE SEQUENCE actes_envelopes_id_seq;
CREATE SEQUENCE actes_included_files_id_seq;
CREATE SEQUENCE actes_messages_id_seq;
CREATE SEQUENCE actes_messages_reponses_seq;
CREATE SEQUENCE actes_messages_workflow_id_seq;
CREATE SEQUENCE actes_transactions_id_seq;
CREATE SEQUENCE actes_transactions_workflow_id_seq;
CREATE SEQUENCE actes_transmission_window_hours_id_seq;
CREATE SEQUENCE actes_transmission_windows_id_seq;
CREATE SEQUENCE authorities_id_seq;
CREATE SEQUENCE authority_departments_id_seq;
CREATE SEQUENCE authority_districts_id_seq;
CREATE SEQUENCE authority_group_siren_id_seq;
CREATE SEQUENCE authority_groups_id_seq;
CREATE SEQUENCE authority_siret_id_seq;
CREATE SEQUENCE dia_transactions_id_seq;
CREATE SEQUENCE dia_transactions_workflow_id_seq;
CREATE SEQUENCE helios_retour_id_seq;
CREATE SEQUENCE helios_transactions_id_seq;
CREATE SEQUENCE helios_transactions_workflow_id_seq;
CREATE SEQUENCE helios_transmission_window_hours_id_seq;
CREATE SEQUENCE helios_transmission_windows_id_seq;
CREATE SEQUENCE logs_id_seq;
CREATE SEQUENCE logs_request_id_seq;
CREATE SEQUENCE mail_annuaire_id_seq;
CREATE SEQUENCE mail_errors_id_seq;
CREATE SEQUENCE mail_groupe_id_seq;
CREATE SEQUENCE mail_included_file_id_seq;
CREATE SEQUENCE mail_transaction_id_seq;
CREATE SEQUENCE message_admin_id_seq;
CREATE SEQUENCE modules_authorities_id_seq;
CREATE SEQUENCE modules_id_seq;
CREATE SEQUENCE modules_params_id_seq;
CREATE SEQUENCE nounce_id_seq;
CREATE SEQUENCE service_user_id_seq;
CREATE SEQUENCE users_id_seq;
CREATE SEQUENCE users_perms_id_seq;
CREATE TABLE actes_batch_files (
    id integer DEFAULT nextval('actes_batch_files_id_seq'::regclass) NOT NULL,
    batch_id integer,
    transaction_id integer,
    filename character varying(1024),
    filesize integer,
    status character varying(10),
    signature text
);
CREATE TABLE actes_batches (
    id integer DEFAULT nextval('actes_batches_id_seq'::regclass) NOT NULL,
    user_id integer,
    submission_date timestamp with time zone,
    storage_dir character varying(1024),
    description character varying(1024),
    num_prefix character varying(16),
    next_suffix integer
);
CREATE TABLE actes_classification_codes (
    id integer DEFAULT nextval('actes_classification_codes_id_seq'::regclass) NOT NULL,
    authority_id integer NOT NULL,
    level integer,
    code integer,
    parent_id integer,
    description character varying(256)
);
CREATE TABLE actes_classification_requests (
    id integer DEFAULT nextval('actes_classification_requests_id_seq'::regclass) NOT NULL,
    request_date timestamp with time zone,
    requested_by integer,
    version_date timestamp with time zone,
    xml_data text
);
CREATE TABLE actes_envelope_serials (
    id integer DEFAULT nextval('actes_envelope_serials_id_seq'::regclass) NOT NULL,
    authority_id integer,
    reset_date timestamp with time zone,
    serial integer
);
CREATE TABLE actes_envelopes (
    id integer DEFAULT nextval('actes_envelopes_id_seq'::regclass) NOT NULL,
    user_id integer,
    submission_date timestamp with time zone,
    siren character varying(128),
    department character(3),
    district character(1),
    authority_type_code integer,
    name character varying(100),
    telephone character varying(25),
    email character varying(255),
    file_path character varying(1024),
    file_size integer,
    return_mail character varying(1024),
    warning_sent character(1) DEFAULT NULL::bpchar
);
CREATE TABLE actes_included_files (
    id integer DEFAULT nextval('actes_included_files_id_seq'::regclass) NOT NULL,
    envelope_id integer,
    transaction_id integer,
    filename character varying(512),
    filetype character varying(64),
    filesize integer,
    signature text,
    posted_filename character varying(512),
    sha1 character varying(256) DEFAULT ''::character varying
);
CREATE TABLE actes_messages (
    id integer DEFAULT nextval('actes_messages_id_seq'::regclass) NOT NULL,
    actes_transactions_id integer,
    messages_type integer,
    actes_messages_status_id integer,
    received_date timestamp with time zone
);
CREATE TABLE actes_messages_reponses (
    id integer DEFAULT nextval('actes_messages_reponses_seq'::regclass) NOT NULL,
    actes_messages_id integer,
    dir_path character varying(4096),
    xml_filename character varying(512),
    filename character varying(512),
    agreement boolean,
    file_size integer,
    warning_sent boolean DEFAULT false,
    first_sent_time timestamp with time zone,
    actual_sent_time timestamp with time zone
);
CREATE TABLE actes_messages_status (
    id integer NOT NULL,
    nom character(64)
);
CREATE TABLE actes_messages_workflow (
    id integer DEFAULT nextval('actes_messages_workflow_id_seq'::regclass) NOT NULL,
    actes_messages_id integer,
    actes_messages_status_id integer,
    action_time timestamp with time zone,
    message character varying(1024)
);
CREATE TABLE actes_natures (
    id integer NOT NULL,
    short_descr character varying(5),
    descr character varying(128)
);
CREATE TABLE actes_status (
    id integer NOT NULL,
    name character varying(64)
);
CREATE TABLE actes_transactions (
    id integer DEFAULT nextval('actes_transactions_id_seq'::regclass) NOT NULL,
    envelope_id integer NOT NULL,
    type character varying(5),
    related_transaction_id integer,
    nature_code character varying(10),
    nature_descr character varying(128),
    title character varying(128),
    subject character varying(512),
    number character varying(20),
    classification character varying(128),
    classification_date timestamp with time zone,
    decision_date timestamp with time zone,
    unique_id character varying(128),
    auto_broadcasted boolean DEFAULT false,
    archive_url character varying(1024),
    broadcast_emails text,
    broadcast_send_sources integer,
    broadcasted boolean DEFAULT false,
    type_reponse integer,
    last_status_id integer,
    user_id integer,
    authority_id integer,
    sae_transfer_identifier character varying(256),
    antivirus_check boolean DEFAULT false,
    classification_string character varying(64),
    date_affichage date
);
CREATE TABLE actes_transactions_workflow (
    id integer DEFAULT nextval('actes_transactions_workflow_id_seq'::regclass) NOT NULL,
    transaction_id integer,
    status_id integer,
    date timestamp with time zone,
    message character varying(512),
    flux_retour text
);
CREATE TABLE actes_transmission_window_hours (
    id integer DEFAULT nextval('actes_transmission_window_hours_id_seq'::regclass) NOT NULL,
    transmission_window_id integer,
    window_begin timestamp with time zone,
    window_end timestamp with time zone,
    consumed integer
);
CREATE TABLE actes_transmission_windows (
    id integer DEFAULT nextval('actes_transmission_windows_id_seq'::regclass) NOT NULL,
    rate_limit integer
);
CREATE TABLE authorities (
    id integer DEFAULT nextval('authorities_id_seq'::regclass) NOT NULL,
    authority_type_id integer,
    status integer,
    name character varying(255),
    email character varying(255),
    ext_siret character varying(5),
    siren character varying(10),
    agreement character varying(64),
    address character varying(255),
    postal_code integer,
    city character varying(255),
    telephone character varying(25),
    fax character varying(25),
    department character(3),
    district character(1),
    authority_group_id integer,
    broadcast_email text,
    email_mail_securise character varying(256),
    default_broadcast_email text,
    helios_ftp_password character varying(128),
    helios_ftp_login character varying(128),
    helios_ftp_dest character varying(128),
    sae_wsdl character varying(128),
    sae_login character varying(128),
    sae_password character varying(128),
    sae_id_versant text,
    sae_id_archive text,
    sae_numero_aggrement character varying(128),
    sae_originating_agency text,
    new_notification boolean DEFAULT false,
    dia_siret character varying(1024),
    pastell_url character varying(1024),
    pastell_login character varying(255),
    pastell_password character varying(255),
    pastell_id_e integer,
    helios_do_not_verify_nom_fic_unicity boolean DEFAULT false
);
CREATE TABLE authority_departments (
    id integer DEFAULT nextval('authority_departments_id_seq'::regclass) NOT NULL,
    code character varying(3),
    name character varying(128)
);
CREATE TABLE authority_districts (
    id integer DEFAULT nextval('authority_districts_id_seq'::regclass) NOT NULL,
    authority_department_id integer,
    code character varying(1),
    name character varying(128)
);
CREATE TABLE authority_group_siren (
    id integer DEFAULT nextval('authority_group_siren_id_seq'::regclass) NOT NULL,
    authority_group_id integer,
    siren character varying(10)
);
CREATE TABLE authority_groups (
    id integer DEFAULT nextval('authority_groups_id_seq'::regclass) NOT NULL,
    name character varying(128),
    status integer
);
CREATE TABLE authority_siret (
    id integer DEFAULT nextval('authority_siret_id_seq'::regclass) NOT NULL,
    authority_id integer,
    siret character(14),
    date timestamp with time zone
);
CREATE TABLE authority_types (
    id integer NOT NULL,
    parent_type_id integer,
    description character varying(512)
);
CREATE TABLE dia_transactions (
    id integer DEFAULT nextval('dia_transactions_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    filename character varying(1024) NOT NULL,
    file_size integer,
    submission_date timestamp with time zone,
    last_status_id integer,
    accuse_enregistrement character varying(1024) NOT NULL,
    accuse_non_preemption character varying(1024),
    message_id character varying(1024),
    message_xml text
);
CREATE TABLE dia_transactions_workflow (
    id integer DEFAULT nextval('dia_transactions_workflow_id_seq'::regclass) NOT NULL,
    transaction_id integer,
    status_id integer,
    date timestamp with time zone,
    message character varying(512)
);
CREATE TABLE helios_retour (
    id integer DEFAULT nextval('helios_retour_id_seq'::regclass) NOT NULL,
    siren character varying(255),
    filename character varying(255),
    date timestamp with time zone,
    status integer,
    authority_id integer,
    siret character(14)
);
CREATE TABLE helios_status (
    id integer NOT NULL,
    name character varying(64) NOT NULL
);
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
    complete_name character varying(150),
    sae_transfer_identifier character varying(256),
    last_status_id integer,
    archive_url character varying(1024),
    authority_id integer,
    signature_technique boolean DEFAULT false NOT NULL,
    xml_cod_col character(3),
    xml_id_post character varying(7),
    xml_cod_bud character(2)
);
CREATE TABLE helios_transactions_workflow (
    id integer DEFAULT nextval('helios_transactions_workflow_id_seq'::regclass) NOT NULL,
    transaction_id integer NOT NULL,
    status_id integer NOT NULL,
    date timestamp with time zone NOT NULL,
    message character varying(512) NOT NULL
);
CREATE TABLE helios_transmission_window_hours (
    id integer DEFAULT nextval('helios_transmission_window_hours_id_seq'::regclass) NOT NULL,
    transmission_window_id integer,
    window_begin timestamp with time zone,
    window_end timestamp with time zone,
    consumed integer
);
CREATE TABLE helios_transmission_windows (
    id integer DEFAULT nextval('helios_transmission_windows_id_seq'::regclass) NOT NULL,
    rate_limit integer
);
CREATE TABLE logs (
    id integer DEFAULT nextval('logs_id_seq'::regclass) NOT NULL,
    date timestamp with time zone NOT NULL,
    severity integer,
    module character varying(50),
    issuer character varying(30),
    user_id integer,
    visibility character varying(5),
    message text,
    timestamp text,
    authority_id integer,
    authority_group_id integer
);
CREATE TABLE logs_historique (
    id integer NOT NULL,
    date timestamp with time zone,
    severity integer,
    module character varying(50),
    issuer character varying(30),
    user_id integer,
    visibility character varying(5),
    message text,
    timestamp text,
    authority_id integer,
    authority_group_id integer
);
CREATE TABLE logs_request (
    id integer DEFAULT nextval('logs_request_id_seq'::regclass) NOT NULL,
    state integer NOT NULL,
    date_demande timestamp with time zone NOT NULL,
    date_traitement timestamp with time zone,
    user_id_demandeur integer NOT NULL,
    user_id integer,
    authority_id integer,
    authority_group_id integer,
    date_debut date NOT NULL,
    date_fin date NOT NULL
);
CREATE TABLE mail_annuaire (
    id integer DEFAULT nextval('mail_annuaire_id_seq'::regclass) NOT NULL,
    authority_id integer NOT NULL,
    mail_address character varying(128) NOT NULL,
    description character varying(256)
);
CREATE TABLE mail_errors (
    id integer DEFAULT nextval('mail_errors_id_seq'::regclass) NOT NULL,
    mail_message_emis_id character(128),
    date_registered timestamp with time zone,
    message_retour text
);
CREATE TABLE mail_groupe (
    id integer DEFAULT nextval('mail_groupe_id_seq'::regclass) NOT NULL,
    authority_id integer,
    name character varying(128) NOT NULL
);
CREATE TABLE mail_included_file (
    id integer DEFAULT nextval('mail_included_file_id_seq'::regclass) NOT NULL,
    mail_transaction_id integer NOT NULL,
    filename character varying(512) NOT NULL,
    filetype character varying(64) NOT NULL,
    filesize integer NOT NULL
);
CREATE TABLE mail_message_emis (
    id character(128) NOT NULL,
    mail_transaction_id integer NOT NULL,
    email character varying(256) NOT NULL,
    type_envoi character varying(64) NOT NULL,
    ack boolean DEFAULT false NOT NULL,
    ack_date timestamp with time zone
);
CREATE TABLE mail_transaction (
    id integer DEFAULT nextval('mail_transaction_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    objet character varying(1024) NOT NULL,
    message character varying(2048) NOT NULL,
    fn_download character varying(512),
    status character varying(40) NOT NULL,
    date_envoi timestamp with time zone,
    password character varying(64)
);
CREATE TABLE mail_user_groupe (
    id_user integer,
    id_groupe integer
);
CREATE TABLE message_admin (
    id integer DEFAULT nextval('message_admin_id_seq'::regclass) NOT NULL,
    titre text NOT NULL,
    niveau integer DEFAULT 1,
    date_publication timestamp with time zone,
    date_retrait timestamp with time zone,
    user_id integer NOT NULL,
    message text NOT NULL,
    is_publie boolean DEFAULT false,
    user_id_publieur integer,
    is_retire boolean DEFAULT false,
    user_id_retireur integer
);
CREATE TABLE modules (
    id integer DEFAULT nextval('modules_id_seq'::regclass) NOT NULL,
    name character varying(50),
    description character varying(128),
    menu_entry character varying(128),
    status integer DEFAULT 1 NOT NULL
);
CREATE TABLE modules_authorities (
    id integer DEFAULT nextval('modules_authorities_id_seq'::regclass) NOT NULL,
    module_id integer NOT NULL,
    authority_id integer NOT NULL
);
CREATE TABLE modules_params (
    id integer DEFAULT nextval('modules_params_id_seq'::regclass) NOT NULL,
    module_id integer NOT NULL,
    name character varying(64),
    value character varying(256),
    description character varying(512)
);
CREATE TABLE nounce (
    id integer DEFAULT nextval('nounce_id_seq'::regclass) NOT NULL,
    nounce character varying(255),
    login character varying(255),
    hash character varying(255),
    creation timestamp with time zone,
    authority_id integer
);
CREATE TABLE openstack_swift_counter (
    container character varying(32) NOT NULL,
    last_insert_id integer NOT NULL
);
CREATE TABLE service_user (
    id integer DEFAULT nextval('service_user_id_seq'::regclass) NOT NULL,
    authority_id integer,
    name character varying(128) NOT NULL,
    parent_id integer
);
CREATE TABLE service_user_content (
    id_service integer,
    id_user integer
);
CREATE TABLE users (
    id integer DEFAULT nextval('users_id_seq'::regclass) NOT NULL,
    email character varying(255),
    subject_dn character varying(512) NOT NULL,
    issuer_dn character varying(512) NOT NULL,
    name character varying(100),
    givenname character varying(100),
    telephone character varying(25),
    role character varying(5) NOT NULL,
    authority_id integer NOT NULL,
    status integer,
    certificate text,
    cert_not_before timestamp with time zone,
    cert_not_after timestamp with time zone,
    cert_serial text,
    authority_group_id integer,
    login character varying(128) DEFAULT NULL::character varying,
    password character varying(128) DEFAULT NULL::character varying,
    certificate_rgs_2_etoiles text,
    certificate_hash character varying(64)
);
CREATE TABLE users_perms (
    id integer DEFAULT nextval('users_perms_id_seq'::regclass) NOT NULL,
    module_id integer,
    user_id integer,
    perm character varying(10)
);
CREATE INDEX helios_transactions_workflow_date_idx ON helios_transactions_workflow USING btree (date)
CREATE INDEX helios_transactions_workflow_status_id_idx ON helios_transactions_workflow USING btree (status_id)
CREATE INDEX helios_transactions_workflow_transaction_id_idx ON helios_transactions_workflow USING btree (transaction_id)
CREATE INDEX atw_id_date ON actes_transactions_workflow USING btree (transaction_id, date, id)
CREATE INDEX atw_tid_idx ON actes_transactions_workflow USING btree (transaction_id)
CREATE INDEX at_enveloppe_id ON actes_transactions USING btree (envelope_id)
CREATE INDEX at_related_id ON actes_transactions USING btree (related_transaction_id)
CREATE INDEX t2 ON actes_transactions USING btree (number)
CREATE INDEX t3 ON actes_transactions USING btree (authority_id)
CREATE INDEX toto ON actes_included_files USING btree (transaction_id)
CREATE INDEX logs_request_user_id_demandeur_idx ON logs_request USING btree (user_id_demandeur)
CREATE UNIQUE INDEX modules_name_idx ON modules USING btree (name)
CREATE INDEX t4 ON logs USING btree (user_id)
CREATE INDEX authority_index ON logs USING btree (authority_id, id)
CREATE INDEX authority_group_index ON logs USING btree (authority_group_id, id)
CREATE INDEX xml_nomfic_index ON helios_transactions USING btree (xml_nomfic)
CREATE INDEX xml_nomfic_cod_col_index ON helios_transactions USING btree (xml_nomfic, xml_cod_col)
CREATE INDEX u_authority_id ON users USING btree (authority_id, id)
CREATE UNIQUE INDEX users_certificate_login ON users USING btree (subject_dn, issuer_dn, login)
CREATE INDEX users_login ON users USING btree (login)
CREATE INDEX user_certificat_index ON users USING btree (subject_dn, issuer_dn)
CREATE INDEX users_certificate_hash_idx ON users USING btree (certificate_hash)
ALTER TABLE service_user ADD CONSTRAINT service_user_pkey PRIMARY KEY (id);
ALTER TABLE mail_message_emis ADD CONSTRAINT mail_message_emis_pkey PRIMARY KEY (id);
ALTER TABLE actes_transactions_workflow ADD CONSTRAINT actes_transactions_workflow_pkey PRIMARY KEY (id);
ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_pkey PRIMARY KEY (id);
ALTER TABLE mail_included_file ADD CONSTRAINT mail_included_file_pkey PRIMARY KEY (id);
ALTER TABLE openstack_swift_counter ADD CONSTRAINT openstack_swift_counter_pkey PRIMARY KEY (container);
ALTER TABLE actes_transmission_window_hours ADD CONSTRAINT actes_transmission_window_hours_pkey PRIMARY KEY (id);
ALTER TABLE actes_messages_reponses ADD CONSTRAINT actes_messages_reponses_pkey PRIMARY KEY (id);
ALTER TABLE mail_annuaire ADD CONSTRAINT mail_annuaire_pkey PRIMARY KEY (id);
ALTER TABLE nounce ADD CONSTRAINT nounce_pkey PRIMARY KEY (id);
ALTER TABLE authority_types ADD CONSTRAINT authority_types_pkey PRIMARY KEY (id);
ALTER TABLE actes_messages ADD CONSTRAINT actes_messages_pkey PRIMARY KEY (id);
ALTER TABLE mail_transaction ADD CONSTRAINT mail_transaction_pkey PRIMARY KEY (id);
ALTER TABLE modules_params ADD CONSTRAINT modules_params_pkey PRIMARY KEY (id);
ALTER TABLE authority_siret ADD CONSTRAINT authority_siret_pkey PRIMARY KEY (id);
ALTER TABLE authority_districts ADD CONSTRAINT authority_districts_pkey PRIMARY KEY (id);
ALTER TABLE authority_groups ADD CONSTRAINT authority_groups_pkey PRIMARY KEY (id);
ALTER TABLE helios_retour ADD CONSTRAINT helios_retour_pkey PRIMARY KEY (id);
ALTER TABLE actes_status ADD CONSTRAINT actes_status_pkey PRIMARY KEY (id);
ALTER TABLE authority_group_siren ADD CONSTRAINT authority_group_siren_pkey PRIMARY KEY (id);
ALTER TABLE authority_departments ADD CONSTRAINT authority_departments_pkey PRIMARY KEY (id);
ALTER TABLE helios_transmission_window_hours ADD CONSTRAINT helios_transmission_window_hours_pkey PRIMARY KEY (id);
ALTER TABLE actes_classification_codes ADD CONSTRAINT actes_classification_codes_pkey PRIMARY KEY (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_pkey PRIMARY KEY (id);
ALTER TABLE actes_messages_workflow ADD CONSTRAINT actes_messages_workflow_pkey PRIMARY KEY (id);
ALTER TABLE modules_authorities ADD CONSTRAINT modules_authorities_pkey PRIMARY KEY (id);
ALTER TABLE actes_envelopes ADD CONSTRAINT actes_envelopes_pkey PRIMARY KEY (id);
ALTER TABLE authorities ADD CONSTRAINT authorities_pkey PRIMARY KEY (id);
ALTER TABLE dia_transactions_workflow ADD CONSTRAINT dia_transactions_workflow_pkey PRIMARY KEY (id);
ALTER TABLE mail_groupe ADD CONSTRAINT mail_groupe_pkey PRIMARY KEY (id);
ALTER TABLE actes_transactions ADD CONSTRAINT actes_transactions_pkey PRIMARY KEY (id);
ALTER TABLE actes_classification_requests ADD CONSTRAINT actes_classification_requests_pkey PRIMARY KEY (id);
ALTER TABLE helios_transactions ADD CONSTRAINT helios_transactions_pkey PRIMARY KEY (id);
ALTER TABLE actes_envelope_serials ADD CONSTRAINT actes_envelope_serials_pkey PRIMARY KEY (id);
ALTER TABLE dia_transactions ADD CONSTRAINT dia_transactions_pkey PRIMARY KEY (id);
ALTER TABLE helios_transactions_workflow ADD CONSTRAINT helios_transactions_workflow_pkey PRIMARY KEY (id);
ALTER TABLE helios_transmission_windows ADD CONSTRAINT helios_transmission_windows_pkey PRIMARY KEY (id);
ALTER TABLE message_admin ADD CONSTRAINT message_admin_pkey PRIMARY KEY (id);
ALTER TABLE users ADD CONSTRAINT users_pkey PRIMARY KEY (id);
ALTER TABLE mail_errors ADD CONSTRAINT mail_errors_pkey PRIMARY KEY (id);
ALTER TABLE logs_historique ADD CONSTRAINT logs_historique_pkey PRIMARY KEY (id);
ALTER TABLE users_perms ADD CONSTRAINT users_perms_pkey PRIMARY KEY (id);
ALTER TABLE modules ADD CONSTRAINT modules_pkey PRIMARY KEY (id);
ALTER TABLE helios_status ADD CONSTRAINT helios_status_pkey PRIMARY KEY (id);
ALTER TABLE actes_batches ADD CONSTRAINT actes_batches_pkey PRIMARY KEY (id);
ALTER TABLE actes_transmission_windows ADD CONSTRAINT actes_transmission_windows_pkey PRIMARY KEY (id);
ALTER TABLE actes_natures ADD CONSTRAINT actes_natures_pkey PRIMARY KEY (id);
ALTER TABLE actes_messages_status ADD CONSTRAINT actes_messages_status_pkey PRIMARY KEY (id);
ALTER TABLE actes_included_files ADD CONSTRAINT actes_included_files_pkey PRIMARY KEY (id);
ALTER TABLE logs ADD CONSTRAINT logs_pkey PRIMARY KEY (id);
ALTER TABLE mail_annuaire ADD CONSTRAINT mail_annuaire_mail_adresse UNIQUE (authority_id,mail_address);
ALTER TABLE mail_user_groupe ADD CONSTRAINT mail_user_groupe_unique UNIQUE (id_user,id_groupe);
ALTER TABLE mail_groupe ADD CONSTRAINT mail_groupe_unique UNIQUE (authority_id,name);
ALTER TABLE service_user_content ADD CONSTRAINT service_user_content_unique UNIQUE (id_user,id_service);
ALTER TABLE actes_transactions ADD CONSTRAINT at_authority_id FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE mail_user_groupe ADD CONSTRAINT mail_user_groupe_id_user_fkey FOREIGN KEY (id_user) REFERENCES mail_annuaire (id);
ALTER TABLE helios_transmission_window_hours ADD CONSTRAINT helios_transmission_window_hours_transmission_window_id_fk FOREIGN KEY (transmission_window_id) REFERENCES helios_transmission_windows (id);
ALTER TABLE modules_params ADD CONSTRAINT modules_params_id_fk FOREIGN KEY (module_id) REFERENCES modules (id);
ALTER TABLE actes_transactions_workflow ADD CONSTRAINT actes_transactions_workflow_status_id_fk FOREIGN KEY (status_id) REFERENCES actes_status (id);
ALTER TABLE actes_transmission_window_hours ADD CONSTRAINT actes_transmission_window_hours_transmission_window_id_fk FOREIGN KEY (transmission_window_id) REFERENCES actes_transmission_windows (id);
ALTER TABLE actes_included_files ADD CONSTRAINT actes_included_files_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES actes_transactions (id);
ALTER TABLE actes_messages_workflow ADD CONSTRAINT actes_messages_workflow_actes_messages_id_fkey FOREIGN KEY (actes_messages_id) REFERENCES actes_messages (id);
ALTER TABLE authorities ADD CONSTRAINT authorities_authority_type_id_fk FOREIGN KEY (authority_type_id) REFERENCES authority_types (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_user_id_demandeur_fkey FOREIGN KEY (user_id_demandeur) REFERENCES users (id);
ALTER TABLE dia_transactions_workflow ADD CONSTRAINT dia_transactions_workflow_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES dia_transactions (id);
ALTER TABLE helios_retour ADD CONSTRAINT helios_retour_authority_id FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE actes_included_files ADD CONSTRAINT actes_included_files_envelope_id_fk FOREIGN KEY (envelope_id) REFERENCES actes_envelopes (id);
ALTER TABLE modules_authorities ADD CONSTRAINT modules_authorities_module_id_fk FOREIGN KEY (module_id) REFERENCES modules (id);
ALTER TABLE logs ADD CONSTRAINT logs_authority_id FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE actes_transactions ADD CONSTRAINT actes_transactions_envelope_id_fk FOREIGN KEY (envelope_id) REFERENCES actes_envelopes (id);
ALTER TABLE logs ADD CONSTRAINT logs_authority_group_id FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE actes_envelopes ADD CONSTRAINT actes_envelopes_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE users_perms ADD CONSTRAINT users_perms_module_id_fk FOREIGN KEY (module_id) REFERENCES modules (id);
ALTER TABLE authority_types ADD CONSTRAINT authority_types_parent_type_id_fk FOREIGN KEY (parent_type_id) REFERENCES authority_types (id);
ALTER TABLE users_perms ADD CONSTRAINT users_perms_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE actes_messages ADD CONSTRAINT actes_messages_actes_transactions_id_fkey FOREIGN KEY (actes_transactions_id) REFERENCES actes_transactions (id);
ALTER TABLE actes_batches ADD CONSTRAINT actes_batches_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_user_id_fkey FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE actes_envelope_serials ADD CONSTRAINT actes_envelope_serials_authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE authority_districts ADD CONSTRAINT authority_districts_authority_department_id_fkey FOREIGN KEY (authority_department_id) REFERENCES authority_departments (id);
ALTER TABLE users ADD CONSTRAINT users_entity_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE authorities ADD CONSTRAINT authorities_authority_group_id_fk FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE users ADD CONSTRAINT users_authority_group_id_fk FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE authority_group_siren ADD CONSTRAINT authority_group_siren_authority_group_id_fk FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE message_admin ADD CONSTRAINT message_admin_user_id_retireur_fkey FOREIGN KEY (user_id_retireur) REFERENCES users (id);
ALTER TABLE modules_authorities ADD CONSTRAINT modules_authorities_authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE message_admin ADD CONSTRAINT message_admin_user_id_publieur_fkey FOREIGN KEY (user_id_publieur) REFERENCES users (id);
ALTER TABLE actes_transactions ADD CONSTRAINT at_user_id FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE message_admin ADD CONSTRAINT message_admin_user_id_fkey FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE actes_transactions ADD CONSTRAINT actes_transactions_related_transaction_id_fk FOREIGN KEY (related_transaction_id) REFERENCES actes_transactions (id);
ALTER TABLE helios_transactions ADD CONSTRAINT helios_transactions_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE actes_classification_codes ADD CONSTRAINT actes_classification_codes_authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE helios_transactions ADD CONSTRAINT at_authority_id FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE actes_classification_codes ADD CONSTRAINT actes_classification_codes_parent_id_fk FOREIGN KEY (parent_id) REFERENCES actes_classification_codes (id);
ALTER TABLE actes_classification_requests ADD CONSTRAINT actes_classification_requests_requested_by_fk FOREIGN KEY (requested_by) REFERENCES users (id);
ALTER TABLE actes_messages_workflow ADD CONSTRAINT actes_messages_workflow_actes_messages_status_id_fkey FOREIGN KEY (actes_messages_status_id) REFERENCES actes_messages_status (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_authority_id_fkey FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE authority_siret ADD CONSTRAINT authority_siret_authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_authority_group_id_fkey FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE nounce ADD CONSTRAINT authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE actes_messages_reponses ADD CONSTRAINT actes_messages_reponses_actes_messages_id_fkey FOREIGN KEY (actes_messages_id) REFERENCES actes_messages (id);
ALTER TABLE actes_messages ADD CONSTRAINT actes_messages_actes_messages_status_id_fkey FOREIGN KEY (actes_messages_status_id) REFERENCES actes_messages_status (id);
ALTER TABLE service_user_content ADD CONSTRAINT service_user_content_id_user_fkey FOREIGN KEY (id_user) REFERENCES users (id);
ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_batch_id_fk FOREIGN KEY (batch_id) REFERENCES actes_batches (id);
ALTER TABLE mail_user_groupe ADD CONSTRAINT mail_user_groupe_id_groupe_fkey FOREIGN KEY (id_groupe) REFERENCES mail_groupe (id);
ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES actes_transactions (id);
ALTER TABLE service_user ADD CONSTRAINT service_user_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES service_user (id);
ALTER TABLE service_user_content ADD CONSTRAINT service_user_content_id_service_fkey FOREIGN KEY (id_service) REFERENCES service_user (id);
ALTER TABLE actes_transactions_workflow ADD CONSTRAINT actes_transactions_workflow_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES actes_transactions (id);
