
CREATE INDEX aif_ti ON actes_included_files(transaction_id);
	
CREATE INDEX l_u ON logs(user_id);

ALTER TABLE helios_transactions ADD PRIMARY KEY (id);

CREATE INDEX at_lsi_ac ON actes_transactions(last_status_id,antivirus_check);

CREATE INDEX acc_ai ON actes_classification_codes (authority_id);
 
CREATE INDEX mt_ui ON mail_transaction(user_id);
 
CREATE INDEX aif_ei ON actes_included_files(envelope_id);
 
ALTER TABLE logs ADD PRIMARY KEY (id);
 