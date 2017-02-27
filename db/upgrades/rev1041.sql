ALTER TABLE helios_transactions ADD COLUMN xml_cod_col CHAR(3);
ALTER TABLE helios_transactions ADD COLUMN xml_id_post CHAR(7);
ALTER TABLE helios_transactions ADD COLUMN xml_col_bud CHAR(2);

DROP INDEX xml_nomfic_index_unique;

CREATE INDEX xml_nomfic_index ON helios_transactions(xml_nomfic);
CREATE INDEX xml_nomfic_cod_col_index ON helios_transactions(xml_nomfic,xml_cod_col);

ALTER TABLE authorities ADD COLUMN helios_nomfic_unicity_verification BOOL DEFAULT TRUE ;

