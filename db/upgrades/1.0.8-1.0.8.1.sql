SET CLIENT_ENCODING TO 'LATIN9';


alter table authorities add column helios_ftp_password varchar (128) ; 
alter table authorities add column helios_ftp_login varchar (128); 
alter table authorities add column helios_ftp_dest varchar (128); 
alter table authorities add column ext_siret varchar (5); 
