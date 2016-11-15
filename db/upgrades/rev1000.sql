
CREATE SEQUENCE nounce_id_seq;
CREATE TABLE nounce (
  id integer PRIMARY KEY DEFAULT nextval('nounce_id_seq'),
  nounce VARCHAR(255),
  login VARCHAR(255),
  hash VARCHAR (255),
  creation TIMESTAMP WITH TIME ZONE
);