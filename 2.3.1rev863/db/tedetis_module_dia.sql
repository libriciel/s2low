SET CLIENT_ENCODING TO 'LATIN9';

INSERT INTO modules (name, description, menu_entry, status)
    VALUES ('dia', 'Module DIA', 'Transactions DIA', 1);

CREATE SEQUENCE dia_transactions_id_seq;
CREATE TABLE dia_transactions (
    id integer DEFAULT nextval('dia_transactions_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    filename character varying(1024) NOT NULL,
    file_size integer,
    submission_date timestamp with time zone,
	last_status_id INT,
	accuse_enregistrement character varying(1024) NOT NULL

);

ALTER TABLE ONLY dia_transactions
ADD CONSTRAINT dia_transactions_pkey PRIMARY KEY (id);
    
CREATE SEQUENCE dia_transactions_workflow_id_seq;
CREATE TABLE dia_transactions_workflow (
	id integer PRIMARY KEY DEFAULT nextval('dia_transactions_workflow_id_seq'),
	transaction_id integer,
    status_id integer,
	date timestamp with time zone,
	message varchar(512)
);

ALTER TABLE dia_transactions_workflow 
ADD CONSTRAINT dia_transactions_workflow_transaction_id_fk 
FOREIGN KEY (transaction_id) REFERENCES dia_transactions(id);
