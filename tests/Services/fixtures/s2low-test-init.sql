-- Remise à zéro de la BDD
DELETE FROM logs;
DELETE FROM logs_historique;

DELETE FROM authority_pastell_config;
DELETE FROM actes_classification_codes;
DELETE FROM actes_classification_requests;
DELETE FROM actes_included_files;
DELETE FROM actes_transactions_workflow;
DELETE FROM actes_transactions;
DELETE FROM actes_envelopes;
DELETE FROM actes_type_pj;
DELETE FROM actes_natures;
DELETE FROM actes_status;
DELETE FROM actes_transmission_window_hours;
DELETE FROM actes_transmission_windows;

DELETE FROM helios_transactions;
DELETE FROM helios_retour;
DELETE FROM helios_transactions_workflow;

DELETE FROM users_perms;
DELETE FROM service_user_content;
DELETE FROM service_user;
DELETE FROM logs_request;
DELETE FROM message_admin;
DELETE FROM users;
DELETE FROM modules_authorities;
DELETE FROM authority_siret;
DELETE FROM nounce;
DELETE FROM authorities;
DELETE FROM authority_types;
DELETE FROM authority_group_siren;
DELETE FROM authority_groups;
DELETE FROM modules;
DELETE FROM logs;

DELETE FROM mail_transaction;

-- Insertion des natures d'actes
INSERT INTO actes_natures VALUES (3, 'AI', 'Arretes individuelles');
INSERT INTO actes_natures VALUES (1, 'DE', 'Deliberations');
INSERT INTO actes_status VALUES (-1, 'Erreur');
INSERT INTO actes_status VALUES (1, 'Post�');
INSERT INTO actes_status VALUES (2, 'En attente de transmission');
INSERT INTO actes_status VALUES (3, 'Transmis');
INSERT INTO actes_status VALUES (4, 'Acquittement re�u');
INSERT INTO actes_status VALUES (13, 'Archiv� par le SAE');
INSERT INTO actes_status VALUES (14, 'Erreur lors de l''archivage');
INSERT INTO actes_status VALUES (19, 'En attente de transmission au SAE');
INSERT INTO actes_status VALUES (20, 'Erreur lors de l''envoi au SAE');
INSERT INTO actes_status VALUES (21, 'Document re�u (pas d''AR)');
INSERT INTO actes_status VALUES (12, 'Envoy� au SAE');
INSERT INTO actes_status VALUES (18, 'En attente d''�tre sign�');

-- Création des modules
INSERT INTO modules VALUES (1, 'actes', 'Module Actes', 'Transactions Actes', 1);
INSERT INTO modules VALUES (2, 'helios', 'Module Helios', 'Transactions Helios', 1);
INSERT INTO modules VALUES (3, 'mail', 'Module Mail', 'Transactions Mail', 1);

-- Création d'un groupe
INSERT INTO authority_groups VALUES (1, 'Groupe de test', 1);

-- Création d'une collectivité
INSERT INTO authority_types VALUES (1, NULL, 'R�gion');
INSERT INTO authorities VALUES (1, 1, 1, 'Bourg-en-Bresse', NULL, NULL, '123456789', NULL, NULL, NULL, NULL, NULL, NULL, '001', '1', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, true, NULL, NULL, NULL, NULL, NULL, false);
INSERT INTO modules_authorities VALUES (1, 1, 1);  -- Accès au module Actes
INSERT INTO modules_authorities VALUES (2, 2, 1);  -- Accès au module Helios
INSERT INTO modules_authorities VALUES (3, 3, 1);  -- Accès au module Mail

-- Création d'un utilisateur


-- INSERT INTO mail_annuaire (authority_id,mail_address) VALUES (1,'test@groupemail.fr');