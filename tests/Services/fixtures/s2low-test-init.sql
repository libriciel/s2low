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
INSERT INTO users VALUES (1, 'eric@sigmalis.com', 'test_subject', 'test_issuer', 'Pommateau', 'Eric', NULL, 'SADM', 1, 1, '-----BEGIN CERTIFICATE-----
MIIFeTCCA2ECAQgwDQYJKoZIhvcNAQEFBQAwgYoxCzAJBgNVBAYTAkZSMQ8wDQYD
VQQIDAZGcmFuY2UxDTALBgNVBAcMBEx5b24xETAPBgNVBAoMCFNpZ21hbGlzMSYw
JAYDVQQDDB1TaWdtYWxpcyBDZXJ0aWZpY2F0ZSBBdXRvcml0eTEgMB4GCSqGSIb3
DQEJARYRZXJpY0BzaWdtYWxpcy5jb20wHhcNMTUwODE5MDgzMzU5WhcNMjUwODE2
MDgzMzU5WjB6MQswCQYDVQQGEwJGUjEPMA0GA1UECAwGRnJhbmNlMQ0wCwYDVQQH
DARMeW9uMREwDwYDVQQKDAhTaWdtYWxpczERMA8GA1UECwwIc2lnbWFsaXMxJTAj
BgNVBAMMHEVyaWNfUG9tbWF0ZWF1X1JHU18yX2V0b2lsZXMwggIiMA0GCSqGSIb3
DQEBAQUAA4ICDwAwggIKAoICAQDYdUMag6AQO7uepqYJm1Uyi/U/zpgIm+8LpWIZ
JsFQj++dXcDHa+fV8TGun8H8tqVMfDwNd+VREgDiatU8v/PZDJw2ZjTETGC1qeN2
eM3ZrOXvur8y8m5j1KPtT9y2M8k204NW0mf/weoYVSulEQbsyoQJfIMu7ALi/XvF
XkGjvpG/BRr8MfSh7GtUtaGJhpGVTwv0gHXXGorixgGPhDNVE8Wr2mn/icfb/hpf
QamO62W/fP4p1thGo5CMhqjyl6PLseU76nD9lUzWZtLSE1/1885zWsHGqD63Vhc/
8Dr89GqCqKdBM4egwlQvT8diTZpeYSRCxHAybiPSAu5WUd0UMARabiQGbXrR+Rqs
+C2W5WkUrwU8bwvpZlPF/BiGWAMayqA3xos7uqHFQjlNtg7wRir4dYxNH/whbl0G
u5dOMbFcFU/mqWpvEPIpIG5Ym7shoUYUH2x8T5TGvIn3a5BUCGmH9n/DH8ybNqD6
3hlsdQ2Bnt4n/nZfS1a326j3EA4eALeQ0vbLxWqoy7hASzkfFO7YDEi2U4rucfAX
JWDq4O3HzPl+aQseken7DBLGfNobl77JKYIadJqbvDXNueTm6+l7r/okg76xCUIQ
r5Sp+x4YpVolt/FG6KY0LNfTnByniWwQoOXh4Az4qGuiGgV+l9gOuZRTr/KrZ6tX
Z9T3iQIDAQABMA0GCSqGSIb3DQEBBQUAA4ICAQCTL+p4cZLzQJ2boA43xX/YdJRT
PNKaka0BywJ5HIkFEm5YNVgxrqfoQZC+DxCqAGJwQm7+HkDZpWr2RkmloVHFranc
pkcWU1Vca5jN9oPg6rMlQLiLz4hnO8XAjcYBR4neAIDd8DP5kwH/Kj36vPqu0ki5
osd70G4ZpsBoW4BXVEPhwLKTBzQvZREsC+654k/JAbAYj/FQba9jaudfbO5xVLbN
lhYKv+Iz+pUGJIP+Sr5wykDuWuyLwnnyvg0WQ0CeUWgG0x4D7Ef828i92ZC/Cump
jaRYKMPYEzitndNW36K1CldGfCuNUqAQmqXIPZqyaqQ0H7N8BnISwdBCojZEljwf
NOxHLrT1MQDDacl5gLEEYKj8JW86UiMAo3ONIi1HYT+eo4Sx/BzH9GhUwT8IUuiH
nl721SzNuIRzB++VurtvQVc5cDumko6Qy+VgRnxPbzk32ortsBYUAFZUpoGA1f1B
W4wgpYN8mfzmXBL88ugP7bWYQLV6wxoBW44IbLnIPJoWP8c13YWC2pC8DIOXzXON
yPThsQ7QoSMwU27XzH1zb+NiD8sHNPgHacK6gSg/ZBj53IMGtElUAw3RRgXbuYnK
eprALP5oks/IqINKST3K68njxMHj/v/hduEkw0dJxD5J/ga9beBhZ2Soe7XqBuUv
YNN6Z4fNWGHPgI7R6w==
-----END CERTIFICATE-----
', NULL, NULL, NULL, 1, NULL, NULL, '-----BEGIN CERTIFICATE-----
MIIFeTCCA2ECAQgwDQYJKoZIhvcNAQEFBQAwgYoxCzAJBgNVBAYTAkZSMQ8wDQYD
VQQIDAZGcmFuY2UxDTALBgNVBAcMBEx5b24xETAPBgNVBAoMCFNpZ21hbGlzMSYw
JAYDVQQDDB1TaWdtYWxpcyBDZXJ0aWZpY2F0ZSBBdXRvcml0eTEgMB4GCSqGSIb3
DQEJARYRZXJpY0BzaWdtYWxpcy5jb20wHhcNMTUwODE5MDgzMzU5WhcNMjUwODE2
MDgzMzU5WjB6MQswCQYDVQQGEwJGUjEPMA0GA1UECAwGRnJhbmNlMQ0wCwYDVQQH
DARMeW9uMREwDwYDVQQKDAhTaWdtYWxpczERMA8GA1UECwwIc2lnbWFsaXMxJTAj
BgNVBAMMHEVyaWNfUG9tbWF0ZWF1X1JHU18yX2V0b2lsZXMwggIiMA0GCSqGSIb3
DQEBAQUAA4ICDwAwggIKAoICAQDYdUMag6AQO7uepqYJm1Uyi/U/zpgIm+8LpWIZ
JsFQj++dXcDHa+fV8TGun8H8tqVMfDwNd+VREgDiatU8v/PZDJw2ZjTETGC1qeN2
eM3ZrOXvur8y8m5j1KPtT9y2M8k204NW0mf/weoYVSulEQbsyoQJfIMu7ALi/XvF
XkGjvpG/BRr8MfSh7GtUtaGJhpGVTwv0gHXXGorixgGPhDNVE8Wr2mn/icfb/hpf
QamO62W/fP4p1thGo5CMhqjyl6PLseU76nD9lUzWZtLSE1/1885zWsHGqD63Vhc/
8Dr89GqCqKdBM4egwlQvT8diTZpeYSRCxHAybiPSAu5WUd0UMARabiQGbXrR+Rqs
+C2W5WkUrwU8bwvpZlPF/BiGWAMayqA3xos7uqHFQjlNtg7wRir4dYxNH/whbl0G
u5dOMbFcFU/mqWpvEPIpIG5Ym7shoUYUH2x8T5TGvIn3a5BUCGmH9n/DH8ybNqD6
3hlsdQ2Bnt4n/nZfS1a326j3EA4eALeQ0vbLxWqoy7hASzkfFO7YDEi2U4rucfAX
JWDq4O3HzPl+aQseken7DBLGfNobl77JKYIadJqbvDXNueTm6+l7r/okg76xCUIQ
r5Sp+x4YpVolt/FG6KY0LNfTnByniWwQoOXh4Az4qGuiGgV+l9gOuZRTr/KrZ6tX
Z9T3iQIDAQABMA0GCSqGSIb3DQEBBQUAA4ICAQCTL+p4cZLzQJ2boA43xX/YdJRT
PNKaka0BywJ5HIkFEm5YNVgxrqfoQZC+DxCqAGJwQm7+HkDZpWr2RkmloVHFranc
pkcWU1Vca5jN9oPg6rMlQLiLz4hnO8XAjcYBR4neAIDd8DP5kwH/Kj36vPqu0ki5
osd70G4ZpsBoW4BXVEPhwLKTBzQvZREsC+654k/JAbAYj/FQba9jaudfbO5xVLbN
lhYKv+Iz+pUGJIP+Sr5wykDuWuyLwnnyvg0WQ0CeUWgG0x4D7Ef828i92ZC/Cump
jaRYKMPYEzitndNW36K1CldGfCuNUqAQmqXIPZqyaqQ0H7N8BnISwdBCojZEljwf
NOxHLrT1MQDDacl5gLEEYKj8JW86UiMAo3ONIi1HYT+eo4Sx/BzH9GhUwT8IUuiH
nl721SzNuIRzB++VurtvQVc5cDumko6Qy+VgRnxPbzk32ortsBYUAFZUpoGA1f1B
W4wgpYN8mfzmXBL88ugP7bWYQLV6wxoBW44IbLnIPJoWP8c13YWC2pC8DIOXzXON
yPThsQ7QoSMwU27XzH1zb+NiD8sHNPgHacK6gSg/ZBj53IMGtElUAw3RRgXbuYnK
eprALP5oks/IqINKST3K68njxMHj/v/hduEkw0dJxD5J/ga9beBhZ2Soe7XqBuUv
YNN6Z4fNWGHPgI7R6w==
-----END CERTIFICATE-----
', 'ieQoLUcitdU9iZIJLPoIdp8TcUY=');

INSERT INTO users_perms VALUES (64395, 1, 1, 'RW'); -- Permission RW sur le module Actes
INSERT INTO users_perms VALUES (64396, 2, 1, 'RW'); -- Permission RW sur le module Helios
INSERT INTO users_perms VALUES (64397, 3, 1, 'RW'); -- Permission RW sur le module Mail

-- INSERT INTO mail_annuaire (authority_id,mail_address) VALUES (1,'test@groupemail.fr');