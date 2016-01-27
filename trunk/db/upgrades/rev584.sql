ALTER TABLE actes_transactions ADD COLUMN antivirus_check boolean DEFAULT false;
UPDATE actes_transactions SET antivirus_check=true;
