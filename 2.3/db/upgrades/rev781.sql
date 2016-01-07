ALTER TABLE users ADD COLUMN certificate_hash VARCHAR(64);
CREATE INDEX ON users (certificate_hash);