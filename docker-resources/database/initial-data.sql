
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


