
CREATE SEQUENCE message_admin_id_seq;


CREATE TABLE IF NOT EXISTS message_admin (
    id INTEGER PRIMARY KEY DEFAULT nextval('message_admin_id_seq'),
    titre TEXT NOT NULL,
    niveau INT DEFAULT 1,
    date_publication TIMESTAMP WITH TIME ZONE,
    date_retrait TIMESTAMP WITH TIME ZONE,
    user_id INT REFERENCES users(id) NOT NULL,
    message TEXT NOT NULL,
    is_publie BOOLEAN DEFAULT FALSE ,
    user_id_publieur INT REFERENCES users(id),
    is_retire BOOLEAN DEFAULT FALSE,
    user_id_retireur INT REFERENCES  users(id)
);