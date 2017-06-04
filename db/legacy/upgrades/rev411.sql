
ALTER TABLE authorities ADD COLUMN dia_siret varchar(1024) ;
ALTER TABLE dia_transactions ADD COLUMN message_id varchar(1024) ; 
ALTER TABLE dia_transactions ADD COLUMN message_xml text ;