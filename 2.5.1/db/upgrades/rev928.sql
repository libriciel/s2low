-- ATTENTION, C'est le script de migration qui va créer la table, faire passer ce script après la migration !

CREATE TABLE IF NOT EXISTS logs_historique (
  id integer PRIMARY KEY,
  date timestamp with time zone NOT NULL,
  severity integer,
  module varchar(50),
  issuer varchar(30),
  user_id integer,
  visibility varchar(5),
  message text,
  timestamp text,
  authority_id INTEGER,
  authority_group_id INTEGER
);


CREATE INDEX logs_historique_user ON logs_historique(user_id);

ALTER TABLE logs_historique ADD CONSTRAINT logs_historique_authority_id FOREIGN KEY (authority_id) REFERENCES authorities(id);
CREATE INDEX lh_authority_index ON logs_historique USING btree (authority_id,id);

ALTER TABLE logs_historique ADD CONSTRAINT logs_historique_authority_group_id FOREIGN KEY (authority_group_id) REFERENCES authority_groups(id);
CREATE INDEX lh_authority_group_index ON logs_historique USING btree (authority_group_id,id);

CREATE INDEX lh_date ON logs_historique USING btree (date,id);

CREATE INDEX l_date ON logs USING btree (date,id);