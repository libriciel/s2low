CREATE INDEX at_enveloppe_id ON actes_transactions USING btree (envelope_id);
CREATE INDEX at_related_id ON actes_transactions USING btree (related_transaction_id);
CREATE INDEX atw_id_date ON actes_transactions_workflow USING btree (transaction_id, date, id);
CREATE INDEX u_authority_id ON users USING btree (authority_id, id);

ALTER TABLE actes_transactions ADD COLUMN last_status_id INT;

-- mise à jour des dernier état de la transaction
UPDATE actes_transactions SET last_status_id=(SELECT status_id FROM actes_transactions_workflow atw  WHERE atw.transaction_id=actes_transactions.id  ORDER BY atw.id DESC LIMIT 1);