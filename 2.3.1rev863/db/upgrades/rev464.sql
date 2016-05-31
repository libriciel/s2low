
ALTER TABLE helios_transactions ADD COLUMN authority_id INT;
ALTER TABLE helios_transactions ADD CONSTRAINT 
at_authority_id FOREIGN KEY (authority_id) REFERENCES authorities(id);

UPDATE helios_transactions SET authority_id=users.authority_id FROM users WHERE users.id=helios_transactions.user_id;
