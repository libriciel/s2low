
CREATE INDEX at_user_id ON actes_transactions USING btree (user_id, last_status_id, id);
