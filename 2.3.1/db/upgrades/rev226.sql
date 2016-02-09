BEGIN;
DROP INDEX users_login;
CREATE INDEX users_login ON users(login);
CREATE UNIQUE INDEX users_certificate_login ON users(subject_dn, issuer_dn, login);
COMMIT;
