ALTER TABLE nounce ADD COLUMN authority_id INTEGER;
ALTER TABLE nounce ADD CONSTRAINT authority_id_fk FOREIGN KEY (authority_id) REFERENCES authorities(id);

