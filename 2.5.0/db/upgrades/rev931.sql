
CREATE SEQUENCE logs_request_id_seq;


CREATE TABLE IF NOT EXISTS logs_request (
  id INTEGER PRIMARY KEY DEFAULT nextval('logs_request_id_seq'),
  state INTEGER NOT NULL ,
  date_demande TIMESTAMP WITH TIME ZONE NOT NULL ,
  date_traitement TIMESTAMP WITH TIME ZONE,
  user_id_demandeur INTEGER REFERENCES users(id) NOT NULL,
  user_id INTEGER REFERENCES users(id),
  authority_id INTEGER REFERENCES authorities(id),
  authority_group_id INTEGER REFERENCES authority_groups(id),
  date_debut DATE NOT NULL,
  date_fin DATE NOT NULL
);

CREATE INDEX ON logs_request (user_id_demandeur);
CREATE INDEX ON logs_request (state);

