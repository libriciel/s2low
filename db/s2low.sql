CREATE SEQUENCE IF NOT EXISTS actes_batch_files_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_batches_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_classification_codes_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_classification_requests_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_envelope_serials_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_envelopes_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_included_files_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_transactions_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_transactions_workflow_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_transmission_window_hours_id_seq;
CREATE SEQUENCE IF NOT EXISTS actes_transmission_windows_id_seq;
CREATE SEQUENCE IF NOT EXISTS authorities_id_seq;
CREATE SEQUENCE IF NOT EXISTS authority_departments_id_seq;
CREATE SEQUENCE IF NOT EXISTS authority_districts_id_seq;
CREATE SEQUENCE IF NOT EXISTS authority_group_siren_id_seq;
CREATE SEQUENCE IF NOT EXISTS authority_groups_id_seq;
CREATE SEQUENCE IF NOT EXISTS authority_pastell_config_id_seq;
CREATE SEQUENCE IF NOT EXISTS authority_siret_id_seq;
CREATE SEQUENCE IF NOT EXISTS helios_retour_id_seq;
CREATE SEQUENCE IF NOT EXISTS helios_transactions_id_seq;
CREATE SEQUENCE IF NOT EXISTS helios_transactions_workflow_id_seq;
CREATE SEQUENCE IF NOT EXISTS helios_transmission_window_hours_id_seq;
CREATE SEQUENCE IF NOT EXISTS helios_transmission_windows_id_seq;
CREATE SEQUENCE IF NOT EXISTS logs_id_seq;
CREATE SEQUENCE IF NOT EXISTS logs_request_id_seq;
CREATE SEQUENCE IF NOT EXISTS mail_annuaire_id_seq;
CREATE SEQUENCE IF NOT EXISTS mail_errors_id_seq;
CREATE SEQUENCE IF NOT EXISTS mail_groupe_id_seq;
CREATE SEQUENCE IF NOT EXISTS mail_included_file_id_seq;
CREATE SEQUENCE IF NOT EXISTS mail_message_emis_id_seq;
CREATE SEQUENCE IF NOT EXISTS mail_transaction_fn_download_seq;
CREATE SEQUENCE IF NOT EXISTS mail_transaction_id_seq;
CREATE SEQUENCE IF NOT EXISTS mail_transaction_message_seq;
CREATE SEQUENCE IF NOT EXISTS mail_transaction_objet_seq;
CREATE SEQUENCE IF NOT EXISTS mail_transaction_status_seq;
CREATE SEQUENCE IF NOT EXISTS mail_transaction_users_id_seq;
CREATE SEQUENCE IF NOT EXISTS message_admin_id_seq;
CREATE SEQUENCE IF NOT EXISTS modules_authorities_id_seq;
CREATE SEQUENCE IF NOT EXISTS modules_id_seq;
CREATE SEQUENCE IF NOT EXISTS modules_params_id_seq;
CREATE SEQUENCE IF NOT EXISTS nounce_id_seq;
CREATE SEQUENCE IF NOT EXISTS service_user_id_seq;
CREATE SEQUENCE IF NOT EXISTS users_id_seq;
CREATE SEQUENCE IF NOT EXISTS users_perms_id_seq;
CREATE TABLE IF NOT EXISTS actes_batch_files (
                                                 id integer DEFAULT nextval('actes_batch_files_id_seq'::regclass) NOT NULL,
                                                 batch_id integer,
                                                 transaction_id integer,
                                                 filename character varying(1024),
                                                 filesize integer,
                                                 status character varying(10),
                                                 signature text
);
CREATE TABLE IF NOT EXISTS actes_batches (
                                             id integer DEFAULT nextval('actes_batches_id_seq'::regclass) NOT NULL,
                                             user_id integer,
                                             submission_date timestamp with time zone,
                                             storage_dir character varying(1024),
                                             description character varying(1024),
                                             num_prefix character varying(16),
                                             next_suffix integer
);
CREATE TABLE IF NOT EXISTS actes_classification_codes (
                                                          id integer DEFAULT nextval('actes_classification_codes_id_seq'::regclass) NOT NULL,
                                                          authority_id integer NOT NULL,
                                                          level integer,
                                                          code integer,
                                                          parent_id integer,
                                                          description character varying(256)
);
CREATE TABLE IF NOT EXISTS actes_classification_requests (
                                                             id integer DEFAULT nextval('actes_classification_requests_id_seq'::regclass) NOT NULL,
                                                             request_date timestamp with time zone,
                                                             requested_by integer,
                                                             version_date timestamp with time zone,
                                                             xml_data bytea,
                                                             xml_data_texte text
);
CREATE TABLE IF NOT EXISTS actes_envelope_serials (
                                                      id integer DEFAULT nextval('actes_envelope_serials_id_seq'::regclass) NOT NULL,
                                                      authority_id integer,
                                                      reset_date timestamp with time zone,
                                                      serial integer
);
CREATE TABLE IF NOT EXISTS actes_envelopes (
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
                                               warning_sent character(1) DEFAULT NULL::bpchar,
                                               is_in_cloud boolean DEFAULT false NOT NULL,
                                               not_available boolean DEFAULT false NOT NULL
);
CREATE TABLE IF NOT EXISTS actes_included_files (
                                                    id integer DEFAULT nextval('actes_included_files_id_seq'::regclass) NOT NULL,
                                                    envelope_id integer,
                                                    transaction_id integer,
                                                    filename character varying(512),
                                                    filetype character varying(512),
                                                    filesize integer,
                                                    signature text,
                                                    posted_filename character varying(512),
                                                    sha1 character varying(256) DEFAULT ''::character varying,
                                                    code_pj character varying(5)
);
CREATE TABLE IF NOT EXISTS actes_natures (
                                             id integer NOT NULL,
                                             short_descr character varying(5),
                                             descr character varying(128)
);
CREATE TABLE IF NOT EXISTS actes_status (
                                            id integer NOT NULL,
                                            name character varying(64)
);
CREATE TABLE IF NOT EXISTS actes_transactions (
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
                                                  classification_string character varying(256),
                                                  document_papier boolean DEFAULT false NOT NULL,
                                                  lu boolean DEFAULT false NOT NULL
);
CREATE TABLE IF NOT EXISTS actes_transactions_workflow (
                                                           id integer DEFAULT nextval('actes_transactions_workflow_id_seq'::regclass) NOT NULL,
                                                           transaction_id integer,
                                                           status_id integer,
                                                           date timestamp with time zone,
                                                           message character varying(512),
                                                           flux_retour bytea,
                                                           flux_retour_texte text
);
CREATE TABLE IF NOT EXISTS actes_transmission_window_hours (
                                                               id integer DEFAULT nextval('actes_transmission_window_hours_id_seq'::regclass) NOT NULL,
                                                               transmission_window_id integer,
                                                               window_begin timestamp with time zone,
                                                               window_end timestamp with time zone,
                                                               consumed integer
);
CREATE TABLE IF NOT EXISTS actes_transmission_windows (
                                                          id integer DEFAULT nextval('actes_transmission_windows_id_seq'::regclass) NOT NULL,
                                                          rate_limit integer
);
CREATE TABLE IF NOT EXISTS actes_type_pj (
                                             nature_id integer,
                                             code character varying(5),
                                             libelle character varying(128)
);
CREATE TABLE IF NOT EXISTS authorities (
                                           id integer DEFAULT nextval('authorities_id_seq'::regclass) NOT NULL,
                                           authority_type_id integer,
                                           status integer,
                                           name character varying(255),
                                           email character varying(255),
                                           ext_siret character varying(5),
                                           siren character varying(10),
                                           agreement character varying(64),
                                           address character varying(255),
                                           postal_code character(20),
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
                                           pastell_url character varying(1024),
                                           pastell_login character varying(255),
                                           pastell_password character varying(255),
                                           pastell_id_e integer,
                                           helios_do_not_verify_nom_fic_unicity boolean DEFAULT false,
                                           descr_mail_securise character varying(256),
                                           helios_use_passtrans boolean DEFAULT false
);
CREATE TABLE IF NOT EXISTS authority_departments (
                                                     id integer DEFAULT nextval('authority_departments_id_seq'::regclass) NOT NULL,
                                                     code character varying(3),
                                                     name character varying(128)
);
CREATE TABLE IF NOT EXISTS authority_districts (
                                                   id integer DEFAULT nextval('authority_districts_id_seq'::regclass) NOT NULL,
                                                   authority_department_id integer,
                                                   code character varying(1),
                                                   name character varying(128)
);
CREATE TABLE IF NOT EXISTS authority_group_siren (
                                                     id integer DEFAULT nextval('authority_group_siren_id_seq'::regclass) NOT NULL,
                                                     authority_group_id integer,
                                                     siren character varying(10)
);
CREATE TABLE IF NOT EXISTS authority_groups (
                                                id integer DEFAULT nextval('authority_groups_id_seq'::regclass) NOT NULL,
                                                name character varying(128),
                                                status integer
);
CREATE TABLE IF NOT EXISTS authority_pastell_config (
                                                        id integer DEFAULT nextval('authority_pastell_config_id_seq'::regclass) NOT NULL,
                                                        authority_id integer NOT NULL,
                                                        module_id integer NOT NULL,
                                                        id_flux character varying NOT NULL,
                                                        action character varying NOT NULL,
                                                        is_auto boolean NOT NULL,
                                                        destination character varying(16),
                                                        transaction_id_min integer,
                                                        transaction_id_max integer DEFAULT 2147483647 NOT NULL
);
CREATE TABLE IF NOT EXISTS authority_siret (
                                               id integer DEFAULT nextval('authority_siret_id_seq'::regclass) NOT NULL,
                                               authority_id integer,
                                               siret character(14),
                                               date timestamp with time zone,
                                               is_blocked boolean DEFAULT false NOT NULL
);
CREATE TABLE IF NOT EXISTS authority_types (
                                               id integer NOT NULL,
                                               parent_type_id integer,
                                               description character varying(512)
);
CREATE TABLE IF NOT EXISTS helios_retour (
                                             id integer DEFAULT nextval('helios_retour_id_seq'::regclass) NOT NULL,
                                             siren character varying(255),
                                             filename character varying(255),
                                             date timestamp with time zone,
                                             status integer,
                                             authority_id integer,
                                             siret character(14),
                                             sha1 character varying(256),
                                             is_in_cloud boolean DEFAULT false NOT NULL,
                                             not_available boolean DEFAULT false NOT NULL,
                                             file_size integer
);
CREATE TABLE IF NOT EXISTS helios_status (
                                             id integer NOT NULL,
                                             name character varying(64) NOT NULL
);
CREATE TABLE IF NOT EXISTS helios_transactions (
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
                                                   xml_cod_bud character(2),
                                                   is_in_cloud boolean DEFAULT false NOT NULL,
                                                   not_available boolean DEFAULT false NOT NULL,
                                                   pes_acquit_is_in_cloud boolean DEFAULT false NOT NULL,
                                                   pes_acquit_not_available boolean DEFAULT false NOT NULL
);
CREATE TABLE IF NOT EXISTS helios_transactions_workflow (
                                                            id integer DEFAULT nextval('helios_transactions_workflow_id_seq'::regclass) NOT NULL,
                                                            transaction_id integer NOT NULL,
                                                            status_id integer NOT NULL,
                                                            date timestamp with time zone NOT NULL,
                                                            message character varying(512) NOT NULL
);
CREATE TABLE IF NOT EXISTS helios_transmission_window_hours (
                                                                id integer DEFAULT nextval('helios_transmission_window_hours_id_seq'::regclass) NOT NULL,
                                                                transmission_window_id integer,
                                                                window_begin timestamp with time zone,
                                                                window_end timestamp with time zone,
                                                                consumed integer
);
CREATE TABLE IF NOT EXISTS helios_transmission_windows (
                                                           id integer DEFAULT nextval('helios_transmission_windows_id_seq'::regclass) NOT NULL,
                                                           rate_limit integer
);
CREATE TABLE IF NOT EXISTS logs (
                                    id integer DEFAULT nextval('logs_id_seq'::regclass) NOT NULL,
                                    date timestamp with time zone NOT NULL,
                                    severity integer,
                                    module character varying(50),
                                    issuer character varying(30),
                                    user_id integer,
                                    visibility character varying(5),
                                    message text,
                                    authority_id integer,
                                    authority_group_id integer
);
CREATE TABLE IF NOT EXISTS logs_historique (
                                               id integer NOT NULL,
                                               date timestamp with time zone,
                                               severity integer,
                                               module character varying(50),
                                               issuer character varying(30),
                                               user_id integer,
                                               visibility character varying(5),
                                               message text,
                                               authority_id integer,
                                               authority_group_id integer
);
CREATE TABLE IF NOT EXISTS logs_request (
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
CREATE TABLE IF NOT EXISTS mail_annuaire (
                                             id integer DEFAULT nextval('mail_annuaire_id_seq'::regclass) NOT NULL,
                                             authority_id integer NOT NULL,
                                             mail_address character varying(128) NOT NULL,
                                             description character varying(256)
);
CREATE TABLE IF NOT EXISTS mail_errors (
                                           id integer DEFAULT nextval('mail_errors_id_seq'::regclass) NOT NULL,
                                           mail_message_emis_id character(128),
                                           date_registered timestamp with time zone,
                                           message_retour text
);
CREATE TABLE IF NOT EXISTS mail_groupe (
                                           id integer DEFAULT nextval('mail_groupe_id_seq'::regclass) NOT NULL,
                                           authority_id integer,
                                           name character varying(128) NOT NULL
);
CREATE TABLE IF NOT EXISTS mail_included_file (
                                                  id integer DEFAULT nextval('mail_included_file_id_seq'::regclass) NOT NULL,
                                                  mail_transaction_id integer NOT NULL,
                                                  filename character varying(512) NOT NULL,
                                                  filetype character varying(64) NOT NULL,
                                                  filesize integer NOT NULL
);
CREATE TABLE IF NOT EXISTS mail_message_emis (
                                                 id character(128) NOT NULL,
                                                 mail_transaction_id integer NOT NULL,
                                                 email character varying(256) NOT NULL,
                                                 type_envoi character varying(64) NOT NULL,
                                                 ack boolean DEFAULT false NOT NULL,
                                                 ack_date timestamp with time zone
);
CREATE TABLE IF NOT EXISTS mail_transaction (
                                                id integer DEFAULT nextval('mail_transaction_id_seq'::regclass) NOT NULL,
                                                user_id integer NOT NULL,
                                                objet character varying(1024) NOT NULL,
                                                message character varying(2048) NOT NULL,
                                                fn_download character varying(512),
                                                status character varying(40) NOT NULL,
                                                date_envoi timestamp with time zone,
                                                password character varying(64),
                                                is_in_cloud boolean DEFAULT false NOT NULL,
                                                not_available boolean DEFAULT false NOT NULL
);
CREATE TABLE IF NOT EXISTS mail_user_groupe (
                                                id_user integer,
                                                id_groupe integer
);
CREATE TABLE IF NOT EXISTS message_admin (
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
CREATE TABLE IF NOT EXISTS modules (
                                       id integer DEFAULT nextval('modules_id_seq'::regclass) NOT NULL,
                                       name character varying(50),
                                       description character varying(128),
                                       menu_entry character varying(128),
                                       status integer DEFAULT 1 NOT NULL
);
CREATE TABLE IF NOT EXISTS modules_authorities (
                                                   id integer DEFAULT nextval('modules_authorities_id_seq'::regclass) NOT NULL,
                                                   module_id integer NOT NULL,
                                                   authority_id integer NOT NULL
);
CREATE TABLE IF NOT EXISTS modules_params (
                                              id integer DEFAULT nextval('modules_params_id_seq'::regclass) NOT NULL,
                                              module_id integer NOT NULL,
                                              name character varying(64),
                                              value character varying(256),
                                              description character varying(512)
);
CREATE TABLE IF NOT EXISTS nounce (
                                      id integer DEFAULT nextval('nounce_id_seq'::regclass) NOT NULL,
                                      nounce character varying(255),
                                      login character varying(255),
                                      hash character varying(255),
                                      creation timestamp with time zone,
                                      authority_id integer
);
CREATE TABLE IF NOT EXISTS service_user (
                                            id integer DEFAULT nextval('service_user_id_seq'::regclass) NOT NULL,
                                            authority_id integer,
                                            name character varying(128) NOT NULL,
                                            parent_id integer
);
CREATE TABLE IF NOT EXISTS service_user_content (
                                                    id_service integer,
                                                    id_user integer
);
CREATE TABLE IF NOT EXISTS users (
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
                                     cert_serial character varying(100),
                                     authority_group_id integer,
                                     login character varying(128) DEFAULT NULL::character varying,
                                     password character varying(255) DEFAULT NULL::character varying,
                                     certificate_rgs_2_etoiles text,
                                     certificate_hash character varying(64)
);
CREATE TABLE IF NOT EXISTS users_perms (
                                           id integer DEFAULT nextval('users_perms_id_seq'::regclass) NOT NULL,
                                           module_id integer,
                                           user_id integer,
                                           perm character varying(10)
);
CREATE INDEX IF NOT EXISTS acc_ai ON public.actes_classification_codes USING btree (authority_id);
CREATE UNIQUE INDEX IF NOT EXISTS actes_envelopes_submission_date_id ON public.actes_envelopes USING btree (submission_date, id);
CREATE INDEX IF NOT EXISTS actes_included_files_filename ON public.actes_included_files USING btree (filename);
CREATE INDEX IF NOT EXISTS actes_transactions_authority_id_last_status_id_idx ON public.actes_transactions USING btree (authority_id, last_status_id);
CREATE INDEX IF NOT EXISTS actes_transactions_auto_broadcasted_last_status_id_type_idx ON public.actes_transactions USING btree (auto_broadcasted, last_status_id, type);
CREATE UNIQUE INDEX IF NOT EXISTS actes_transactions_last_status_id_id ON public.actes_transactions USING btree (last_status_id, id);
CREATE UNIQUE INDEX IF NOT EXISTS actes_transactions_unique_id ON public.actes_transactions USING btree (unique_id, type, id);
CREATE INDEX IF NOT EXISTS actes_transactions_user_id_last_status_id_idx ON public.actes_transactions USING btree (user_id, last_status_id);
CREATE INDEX IF NOT EXISTS actes_transactions_workflow_date_idx ON public.actes_transactions_workflow USING btree (date);
CREATE UNIQUE INDEX IF NOT EXISTS ae_id_is_in_cloud ON public.actes_envelopes USING btree (is_in_cloud, not_available, id);
CREATE INDEX IF NOT EXISTS aif_ei ON public.actes_included_files USING btree (envelope_id);
CREATE INDEX IF NOT EXISTS aif_ti ON public.actes_included_files USING btree (transaction_id);
CREATE INDEX IF NOT EXISTS at_enveloppe_id ON public.actes_transactions USING btree (envelope_id);
CREATE INDEX IF NOT EXISTS at_lsi_ac ON public.actes_transactions USING btree (last_status_id, antivirus_check);
CREATE INDEX IF NOT EXISTS at_n ON public.actes_transactions USING btree (number);
CREATE INDEX IF NOT EXISTS at_related_id ON public.actes_transactions USING btree (related_transaction_id);
CREATE INDEX IF NOT EXISTS at_user_id_index ON public.actes_transactions USING btree (user_id, last_status_id, id);
CREATE INDEX IF NOT EXISTS atw_id_date ON public.actes_transactions_workflow USING btree (transaction_id, date, id);
CREATE INDEX IF NOT EXISTS atw_tid_idx ON public.actes_transactions_workflow USING btree (transaction_id);
CREATE INDEX IF NOT EXISTS authority_group_index ON public.logs USING btree (authority_group_id, id);
CREATE INDEX IF NOT EXISTS authority_index ON public.logs USING btree (authority_id, id);
CREATE UNIQUE INDEX IF NOT EXISTS helios_retour_filename_id ON public.helios_retour USING btree (filename, id);
CREATE INDEX IF NOT EXISTS helios_transactions_authority_id_last_status_id_idx ON public.helios_transactions USING btree (authority_id, last_status_id);
CREATE UNIQUE INDEX IF NOT EXISTS helios_transactions_last_status_id ON public.helios_transactions USING btree (last_status_id, id);
CREATE UNIQUE INDEX IF NOT EXISTS helios_transactions_pes_acquit_filename_id ON public.helios_transactions USING btree (acquit_filename, id);
CREATE INDEX IF NOT EXISTS helios_transactions_sha1 ON public.helios_transactions USING btree (sha1);
CREATE UNIQUE INDEX IF NOT EXISTS helios_transactions_sha1_id ON public.helios_transactions USING btree (sha1, id);
CREATE INDEX IF NOT EXISTS helios_transactions_workflow_date_idx ON public.helios_transactions_workflow USING btree (date);
CREATE INDEX IF NOT EXISTS helios_transactions_workflow_status_id_idx ON public.helios_transactions_workflow USING btree (status_id);
CREATE INDEX IF NOT EXISTS helios_transactions_workflow_transaction_id_idx ON public.helios_transactions_workflow USING btree (transaction_id);
CREATE INDEX IF NOT EXISTS hr_id_is_incloud ON public.helios_retour USING btree (is_in_cloud, not_available, id);
CREATE UNIQUE INDEX IF NOT EXISTS ht_pes_aquit_in_cloud ON public.helios_transactions USING btree (pes_acquit_is_in_cloud, pes_acquit_not_available, id);
CREATE UNIQUE INDEX IF NOT EXISTS ht_to_send_in_cloud ON public.helios_transactions USING btree (is_in_cloud, not_available, id);
CREATE INDEX IF NOT EXISTS ht_user_id ON public.helios_transactions USING btree (user_id, id DESC);
CREATE INDEX IF NOT EXISTS l_date ON public.logs USING btree (date, id);
CREATE INDEX IF NOT EXISTS l_u ON public.logs USING btree (user_id);
CREATE INDEX IF NOT EXISTS lh_authority_group_index ON public.logs_historique USING btree (authority_group_id, id);
CREATE INDEX IF NOT EXISTS lh_authority_index ON public.logs_historique USING btree (authority_id, id);
CREATE INDEX IF NOT EXISTS lh_date ON public.logs_historique USING btree (date, id);
CREATE INDEX IF NOT EXISTS logs_historique_user ON public.logs_historique USING btree (user_id);
CREATE INDEX IF NOT EXISTS logs_request_state_idx ON public.logs_request USING btree (state);
CREATE INDEX IF NOT EXISTS logs_request_user_id_demandeur_idx ON public.logs_request USING btree (user_id_demandeur);
CREATE INDEX IF NOT EXISTS logs_user_id_visibility_id_idx ON public.logs USING btree (user_id, visibility, id);
CREATE INDEX IF NOT EXISTS mail_message_emis_mail_transaction_id ON public.mail_message_emis USING btree (mail_transaction_id);
CREATE UNIQUE INDEX IF NOT EXISTS mail_transaction_filename_id ON public.mail_transaction USING btree (fn_download, id);
CREATE UNIQUE INDEX IF NOT EXISTS modules_name_idx ON public.modules USING btree (name);
CREATE INDEX IF NOT EXISTS mt_ui ON public.mail_transaction USING btree (user_id);
CREATE INDEX IF NOT EXISTS u_authority_id ON public.users USING btree (authority_id, id);
CREATE INDEX IF NOT EXISTS users_certificate_hash_idx ON public.users USING btree (certificate_hash);
CREATE UNIQUE INDEX IF NOT EXISTS users_certificate_login ON public.users USING btree (subject_dn, issuer_dn, login);
CREATE INDEX IF NOT EXISTS users_login ON public.users USING btree (login);
CREATE INDEX IF NOT EXISTS xml_nomfic_cod_col_index ON public.helios_transactions USING btree (xml_nomfic, xml_cod_col);
CREATE INDEX IF NOT EXISTS xml_nomfic_index ON public.helios_transactions USING btree (xml_nomfic);
CREATE INDEX IF NOT EXISTS helios_transactions_authority_last_status_id_desc ON public.helios_transactions USING btree (authority_id, last_status_id, id DESC);
CREATE INDEX IF NOT EXISTS helios_retour_authority_id_status_idx ON public.helios_retour USING btree (authority_id, status);
CREATE INDEX IF NOT EXISTS actes_transactions_authority_last_status_id_desc ON public.actes_transactions USING btree (authority_id, last_status_id, id DESC);

ALTER TABLE mail_transaction ADD CONSTRAINT mail_transaction_pkey PRIMARY KEY (id);
ALTER TABLE mail_included_file ADD CONSTRAINT mail_included_file_pkey PRIMARY KEY (id);
ALTER TABLE mail_message_emis ADD CONSTRAINT mail_message_emis_pkey PRIMARY KEY (id);
ALTER TABLE helios_transactions_workflow ADD CONSTRAINT helios_transactions_workflow_pkey PRIMARY KEY (id);
ALTER TABLE mail_errors ADD CONSTRAINT mail_errors_pkey PRIMARY KEY (id);
ALTER TABLE helios_status ADD CONSTRAINT helios_status_pkey PRIMARY KEY (id);
ALTER TABLE authority_districts ADD CONSTRAINT authority_districts_pkey PRIMARY KEY (id);
ALTER TABLE authority_departments ADD CONSTRAINT authority_departments_pkey PRIMARY KEY (id);
ALTER TABLE authority_groups ADD CONSTRAINT authority_groups_pkey PRIMARY KEY (id);
ALTER TABLE actes_classification_codes ADD CONSTRAINT actes_classification_codes_pkey PRIMARY KEY (id);
ALTER TABLE actes_classification_requests ADD CONSTRAINT actes_classification_requests_pkey PRIMARY KEY (id);
ALTER TABLE helios_transactions ADD CONSTRAINT helios_transactions_pkey PRIMARY KEY (id);
ALTER TABLE actes_included_files ADD CONSTRAINT actes_included_files_pkey PRIMARY KEY (id);
ALTER TABLE actes_transactions ADD CONSTRAINT actes_transactions_pkey PRIMARY KEY (id);
ALTER TABLE actes_envelopes ADD CONSTRAINT actes_envelopes_pkey PRIMARY KEY (id);
ALTER TABLE actes_transmission_window_hours ADD CONSTRAINT actes_transmission_window_hours_pkey PRIMARY KEY (id);
ALTER TABLE mail_groupe ADD CONSTRAINT mail_groupe_pkey PRIMARY KEY (id);
ALTER TABLE message_admin ADD CONSTRAINT message_admin_pkey PRIMARY KEY (id);
ALTER TABLE logs ADD CONSTRAINT logs_pkey PRIMARY KEY (id);
ALTER TABLE authority_siret ADD CONSTRAINT authority_siret_pkey PRIMARY KEY (id);
ALTER TABLE actes_status ADD CONSTRAINT actes_status_pkey PRIMARY KEY (id);
ALTER TABLE helios_transmission_windows ADD CONSTRAINT helios_transmission_windows_pkey PRIMARY KEY (id);
ALTER TABLE helios_transmission_window_hours ADD CONSTRAINT helios_transmission_window_hours_pkey PRIMARY KEY (id);
ALTER TABLE actes_natures ADD CONSTRAINT actes_natures_pkey PRIMARY KEY (id);
ALTER TABLE modules_params ADD CONSTRAINT modules_params_pkey PRIMARY KEY (id);
ALTER TABLE authority_types ADD CONSTRAINT authority_types_pkey PRIMARY KEY (id);
ALTER TABLE users_perms ADD CONSTRAINT users_perms_pkey PRIMARY KEY (id);
ALTER TABLE actes_transmission_windows ADD CONSTRAINT actes_transmission_windows_pkey PRIMARY KEY (id);
ALTER TABLE modules ADD CONSTRAINT modules_pkey PRIMARY KEY (id);
ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_pkey PRIMARY KEY (id);
ALTER TABLE mail_annuaire ADD CONSTRAINT mail_annuaire_pkey PRIMARY KEY (id);
ALTER TABLE authorities ADD CONSTRAINT authorities_pkey PRIMARY KEY (id);
ALTER TABLE users ADD CONSTRAINT users_pkey PRIMARY KEY (id);
ALTER TABLE nounce ADD CONSTRAINT nounce_pkey PRIMARY KEY (id);
ALTER TABLE service_user ADD CONSTRAINT service_user_pkey PRIMARY KEY (id);
ALTER TABLE actes_batches ADD CONSTRAINT actes_batches_pkey PRIMARY KEY (id);
ALTER TABLE logs_historique ADD CONSTRAINT logs_historique_pkey PRIMARY KEY (id);
ALTER TABLE authority_pastell_config ADD CONSTRAINT authorities_pastell_config_pkey PRIMARY KEY (id);
ALTER TABLE actes_transactions_workflow ADD CONSTRAINT actes_transactions_workflow_pkey PRIMARY KEY (id);
ALTER TABLE modules_authorities ADD CONSTRAINT modules_authorities_pkey PRIMARY KEY (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_pkey PRIMARY KEY (id);
ALTER TABLE helios_retour ADD CONSTRAINT helios_retour_pkey PRIMARY KEY (id);
ALTER TABLE actes_envelope_serials ADD CONSTRAINT actes_envelope_serials_pkey PRIMARY KEY (id);
ALTER TABLE authority_group_siren ADD CONSTRAINT authority_group_siren_pkey PRIMARY KEY (id);
ALTER TABLE service_user_content ADD CONSTRAINT service_user_content_unique UNIQUE (id_user,id_service);
ALTER TABLE mail_user_groupe ADD CONSTRAINT mail_user_groupe_unique UNIQUE (id_user,id_groupe);
ALTER TABLE mail_groupe ADD CONSTRAINT mail_groupe_unique UNIQUE (authority_id,name);
ALTER TABLE authority_pastell_config ADD CONSTRAINT authority_pastell_config_module_id_fk FOREIGN KEY (module_id) REFERENCES modules (id);
ALTER TABLE authority_pastell_config ADD CONSTRAINT authority_pastell_config_authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE modules_authorities ADD CONSTRAINT modules_authorities_module_id_fk FOREIGN KEY (module_id) REFERENCES modules (id);
ALTER TABLE users ADD CONSTRAINT users_authority_group_id_fk FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE logs ADD CONSTRAINT logs_authority_group_id FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE users_perms ADD CONSTRAINT users_perms_module_id_fk FOREIGN KEY (module_id) REFERENCES modules (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_authority_id_fkey FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE actes_transactions ADD CONSTRAINT actes_transactions_envelope_id_fk FOREIGN KEY (envelope_id) REFERENCES actes_envelopes (id);
ALTER TABLE helios_transactions ADD CONSTRAINT at_authority_id FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES actes_transactions (id);
ALTER TABLE service_user_content ADD CONSTRAINT service_user_content_id_service_fkey FOREIGN KEY (id_service) REFERENCES service_user (id);
ALTER TABLE logs ADD CONSTRAINT logs_authority_id FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE message_admin ADD CONSTRAINT message_admin_user_id_fkey FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE authorities ADD CONSTRAINT authorities_authority_group_id_fk FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE actes_type_pj ADD CONSTRAINT actes_type_pj_nature_id_fk FOREIGN KEY (nature_id) REFERENCES actes_natures (id);
ALTER TABLE actes_transactions_workflow ADD CONSTRAINT actes_transactions_workflow_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES actes_transactions (id);
ALTER TABLE actes_transmission_window_hours ADD CONSTRAINT actes_transmission_window_hours_transmission_window_id_fk FOREIGN KEY (transmission_window_id) REFERENCES actes_transmission_windows (id);
ALTER TABLE actes_transactions_workflow ADD CONSTRAINT actes_transactions_workflow_status_id_fk FOREIGN KEY (status_id) REFERENCES actes_status (id);
ALTER TABLE mail_user_groupe ADD CONSTRAINT mail_user_groupe_id_user_fkey FOREIGN KEY (id_user) REFERENCES mail_annuaire (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_authority_group_id_fkey FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE authority_siret ADD CONSTRAINT authority_siret_authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_user_id_demandeur_fkey FOREIGN KEY (user_id_demandeur) REFERENCES users (id);
ALTER TABLE modules_authorities ADD CONSTRAINT modules_authorities_authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE logs_historique ADD CONSTRAINT logs_historique_authority_id FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE helios_retour ADD CONSTRAINT helios_retour_authority_id FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE logs_historique ADD CONSTRAINT logs_historique_authority_group_id FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
ALTER TABLE nounce ADD CONSTRAINT authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE logs_request ADD CONSTRAINT logs_request_user_id_fkey FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE modules_params ADD CONSTRAINT modules_params_id_fk FOREIGN KEY (module_id) REFERENCES modules (id);
ALTER TABLE service_user ADD CONSTRAINT service_user_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES service_user (id);
ALTER TABLE authorities ADD CONSTRAINT authorities_authority_type_id_fk FOREIGN KEY (authority_type_id) REFERENCES authority_types (id);
ALTER TABLE helios_transmission_window_hours ADD CONSTRAINT helios_transmission_window_hours_transmission_window_id_fk FOREIGN KEY (transmission_window_id) REFERENCES helios_transmission_windows (id);
ALTER TABLE authority_types ADD CONSTRAINT authority_types_parent_type_id_fk FOREIGN KEY (parent_type_id) REFERENCES authority_types (id);
ALTER TABLE users ADD CONSTRAINT users_entity_id_fk FOREIGN KEY (authority_id) REFERENCES authorities (id);
ALTER TABLE users_perms ADD CONSTRAINT users_perms_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE mail_user_groupe ADD CONSTRAINT mail_user_groupe_id_groupe_fkey FOREIGN KEY (id_groupe) REFERENCES mail_groupe (id);
ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_batch_id_fk FOREIGN KEY (batch_id) REFERENCES actes_batches (id);
ALTER TABLE message_admin ADD CONSTRAINT message_admin_user_id_publieur_fkey FOREIGN KEY (user_id_publieur) REFERENCES users (id);
ALTER TABLE service_user_content ADD CONSTRAINT service_user_content_id_user_fkey FOREIGN KEY (id_user) REFERENCES users (id);
ALTER TABLE actes_included_files ADD CONSTRAINT actes_included_files_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES actes_transactions (id);
ALTER TABLE helios_transactions ADD CONSTRAINT helios_transactions_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id);
ALTER TABLE actes_classification_codes ADD CONSTRAINT actes_classification_codes_parent_id_fk FOREIGN KEY (parent_id) REFERENCES actes_classification_codes (id);
ALTER TABLE actes_included_files ADD CONSTRAINT actes_included_files_envelope_id_fk FOREIGN KEY (envelope_id) REFERENCES actes_envelopes (id);
ALTER TABLE actes_transactions ADD CONSTRAINT actes_transactions_related_transaction_id_fk FOREIGN KEY (related_transaction_id) REFERENCES actes_transactions (id);
ALTER TABLE message_admin ADD CONSTRAINT message_admin_user_id_retireur_fkey FOREIGN KEY (user_id_retireur) REFERENCES users (id);
ALTER TABLE authority_group_siren ADD CONSTRAINT authority_group_siren_authority_group_id_fk FOREIGN KEY (authority_group_id) REFERENCES authority_groups (id);
