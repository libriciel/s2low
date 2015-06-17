SET CLIENT_ENCODING TO 'LATIN9';

ALTER TABLE actes_included_files ADD posted_filename VARCHAR(512);

ALTER TABLE actes_transactions ADD archive_url VARCHAR(1024);

CREATE SEQUENCE actes_batches_id_seq;
CREATE TABLE actes_batches (
	id integer PRIMARY KEY DEFAULT nextval('actes_batches_id_seq'),
	user_id integer,
	submission_date timestamp with time zone,
	storage_dir varchar(1024),
	description varchar(1024),
	num_prefix varchar(16),
	next_suffix integer
);

CREATE SEQUENCE actes_batch_files_id_seq;
CREATE TABLE actes_batch_files (
	id integer PRIMARY KEY DEFAULT nextval('actes_batch_files_id_seq'),
	batch_id integer,
    transaction_id integer,
	filename varchar(1024),
	filesize integer,
	status varchar(10),
	signature text
);

ALTER TABLE actes_batches ADD CONSTRAINT actes_batches_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id);
ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_batch_id_fk FOREIGN KEY (batch_id) REFERENCES actes_batches(id);
ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES actes_transactions(id);
